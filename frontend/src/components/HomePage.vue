<template>
    <div>
        <!-- Selector de Artículo -->
        <v-row>
            <v-col cols="12" md="6">
                <v-autocomplete
                    v-model="selectedArticulo"
                    :items="articulos"
                    item-title="displayName"
                    item-value="id"
                    label="Selecciona un artículo"
                    @update:modelValue="fetchVariantes"
                    clearable
                />
            </v-col>
            <v-col cols="12" md="6">
                <v-btn color="primary" @click="fetchArticulos">
                    <v-icon left>mdi-refresh</v-icon> Recargar artículos
                </v-btn>
            </v-col>
        </v-row>

        <!-- Tabla de Variantes -->
        <ResponsiveTable :headers="headers" :items="variantes">
            <!-- Columnas dinámicas de atributos -->
            <template
                v-for="attr in atributosDef"
                #[`item.${attr.nombre}`]="{ item }"
                :key="`attr-${attr.id}`"
            >
                {{ item.atributos[attr.nombre] || "-" }}
            </template>

            <!-- Columnas de acciones -->
            <template #item.actions="{ item }">
                <v-btn icon small @click="openAdjustDialog(item)">
                    <v-icon>mdi-minus-plus</v-icon>
                </v-btn>
                <v-btn icon small @click="openEditDialog(item)">
                    <v-icon>mdi-pencil</v-icon>
                </v-btn>
                <v-btn icon small @click="deleteVariante(item)">
                    <v-icon color="red">mdi-delete</v-icon>
                </v-btn>
            </template>
        </ResponsiveTable>

        <!-- Diálogo Ajustar Stock -->
        <v-dialog v-model="adjustDialog" max-width="400px">
            <v-card>
                <v-card-title
                    >Ajustar stock: {{ editingVariante?.sku }}</v-card-title
                >
                <v-card-text>
                    <v-text-field
                        v-model.number="adjustAmount"
                        label="Cantidad (+/-)"
                        type="number"
                    />
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn text @click="adjustDialog = false">Cancelar</v-btn>
                    <v-btn color="primary" text @click="adjustStock"
                        >Aplicar</v-btn
                    >
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Diálogo Editar Variante -->
        <v-dialog v-model="editDialog" max-width="600px">
            <v-card>
                <v-card-title>
                    {{ formVariante.id ? "Editar" : "Nueva" }} Variante
                </v-card-title>
                <v-card-text>
                    <v-form ref="varianteForm">
                        <v-text-field
                            v-model="formVariante.sku"
                            label="SKU"
                            required
                        />
                        <v-text-field
                            v-model.number="formVariante.stock"
                            label="Stock"
                            type="number"
                            required
                        />
                        <v-text-field
                            v-model.number="formVariante.precio"
                            label="Precio"
                            type="number"
                            required
                        />
                        <div
                            v-for="attr in atributosDef"
                            :key="`edit-${attr.id}`"
                            class="mt-4"
                        >
                            <v-select
                                v-model="formVariante.atributos[attr.nombre]"
                                :items="valoresAtributo[attr.id] || []"
                                item-text="valor"
                                item-value="valor"
                                :label="attr.nombre"
                                clearable
                            />
                        </div>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn text @click="editDialog = false">Cancelar</v-btn>
                    <v-btn color="black" text @click="saveVariante">
                        Guardar
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Loading Overlay -->
        <v-overlay
            :model-value="loading"
            class="d-flex align-center justify-center"
        >
            <v-progress-circular
                indeterminate
                size="64"
                width="6"
                color="primary"
            />
        </v-overlay>
    </div>
</template>

<script>
import axios from "axios";
export default {
    data() {
        return {
            loading: false,
            articulos: [],
            atributosDef: [],
            valoresAtributo: {}, // { atributoId: [ {id, valor}, ... ] }
            variantes: [],
            selectedArticulo: null,
            // Ajuste stock
            adjustDialog: false,
            editingVariante: null,
            adjustAmount: 0,
            // Editar variante
            editDialog: false,
            formVariante: {
                id: null,
                sku: "",
                stock: 0,
                precio: 0,
                atributos: {},
            },
        };
    },
    computed: {
        displayName() {
            return (a) => `${a.numero} – ${a.nombre}`;
        },
        headers() {
            const base = [
                { title: "SKU", key: "sku" },
                { title: "Stock", key: "stock" },
                { title: "Precio", key: "precio" },
            ];
            // agregar columnas por atributo definido
            const dyn = this.atributosDef.map((attr) => ({
                title: attr.nombre,
                key: attr.nombre,
            }));
            return [
                ...base,
                ...dyn,
                { title: "Acciones", key: "actions", sortable: false },
            ];
        },
    },
    methods: {
        // Fetch inicial de articulos y atributos
        async fetchArticulos() {
            this.loading = true;
            try {
                const [aRes, attrRes] = await Promise.all([
                    axios.get("/api/articulos/listar"),
                    axios.get("/api/atributos"),
                ]);
                this.articulos = aRes.data;
                this.atributosDef = attrRes.data;
                // cargar valores de cada atributo
                await Promise.all(
                    this.atributosDef.map(async (a) => {
                        const r = await axios.get(
                            `/api/atributos/${a.id}/valores`
                        );
                        this.$set(this.valoresAtributo, a.id, r.data);
                    })
                );
            } finally {
                this.loading = false;
            }
        },

        // Cuando cambia artículo seleccionado
        async fetchVariantes() {
            if (!this.selectedArticulo) {
                this.variantes = [];
                return;
            }
            this.loading = true;
            try {
                const r = await axios.get(
                    `/api/articulos/${this.selectedArticulo}`
                );
                // asume que el show devuelve { variantes: [ {id,sku,stock,precio,atributos}, … ] }
                this.variantes = r.data.variantes;
            } finally {
                this.loading = false;
            }
        },

        // Ajustar stock
        openAdjustDialog(v) {
            this.editingVariante = v;
            this.adjustAmount = 0;
            this.adjustDialog = true;
        },
        async adjustStock() {
            await axios.post(
                `/api/variantes/${this.editingVariante.id}/ajustar-stock`,
                {
                    cantidad: this.adjustAmount,
                }
            );
            this.adjustDialog = false;
            this.fetchVariantes();
        },

        // Editar variante
        openEditDialog(v = null) {
            if (v) {
                this.formVariante = {
                    id: v.id,
                    sku: v.sku,
                    stock: v.stock,
                    precio: v.precio,
                    atributos: { ...v.atributos },
                };
            } else {
                // nueva variante
                this.formVariante = {
                    id: null,
                    sku: "",
                    stock: 0,
                    precio: 0,
                    atributos: {},
                };
            }
            this.editDialog = true;
        },
        async saveVariante() {
            const payload = {
                sku: this.formVariante.sku,
                stock: this.formVariante.stock,
                precio: this.formVariante.precio,
                atributos: this.formVariante.atributos,
            };
            if (this.formVariante.id) {
                await axios.put(
                    `/api/variantes/${this.formVariante.id}`,
                    payload
                );
            } else {
                // si necesitas endpoint de creación: POST /api/variantes
                await axios.post("/api/variantes", {
                    articulo_id: this.selectedArticulo,
                    ...payload,
                });
            }
            this.editDialog = false;
            this.fetchVariantes();
        },

        // Borrar variante
        async deleteVariante(v) {
            if (!confirm(`¿Eliminar variante ${v.sku}?`)) return;
            await axios.delete(`/api/variantes/${v.id}`);
            this.fetchVariantes();
        },
    },
    mounted() {
        this.fetchArticulos();
    },
};
</script>
