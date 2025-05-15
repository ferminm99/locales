<template>
    <div>
        <!-- Título -->
        <v-row>
            <v-col>
                <h1 class="title font-weight-bold">Gestión de Artículos</h1>
            </v-col>
        </v-row>

        <!-- Filtros -->
        <v-row class="d-flex mb-2">
            <v-col cols="12" sm="6" md="4">
                <v-text-field
                    v-model="search"
                    label="Buscar por nombre o categoría"
                    dense
                    solo
                    clearable
                />
            </v-col>
        </v-row>

        <!-- Botones de acción -->
        <v-row class="d-flex align-center mb-4">
            <v-col cols="auto">
                <v-btn color="black" @click="openAddDialog">
                    <v-icon left>mdi-plus-box</v-icon> Agregar Artículo
                </v-btn>
            </v-col>
            <v-col cols="auto">
                <v-btn color="primary" @click="recalcularPrecios">
                    <v-icon left>mdi-currency-usd</v-icon> Recalcular Precios
                </v-btn>
            </v-col>
            <v-col cols="auto">
                <v-btn color="green" @click="abrirDialogoAumento">
                    <v-icon left>mdi-percent</v-icon> Aumentar Costos
                </v-btn>
            </v-col>
            <v-col cols="auto">
                <v-btn color="orange" class="ml-2" @click="exportarExcel">
                    <v-icon left>mdi-download</v-icon> Exportar Excel
                </v-btn>
            </v-col>
        </v-row>

        <!-- Tabla de artículos -->
        <ResponsiveTable :headers="headers" :items="articulosFiltrados">
            <template #item.actions="{ item }">
                <v-btn icon @click="openEditDialog(item)">
                    <v-icon>mdi-pencil-outline</v-icon>
                </v-btn>
                <v-btn icon @click="openDeleteConfirm(item)">
                    <v-icon>mdi-trash-can-outline</v-icon>
                </v-btn>
            </template>
        </ResponsiveTable>

        <!-- Diálogo Aumento Costos -->
        <v-dialog v-model="dialogoAumento" max-width="400px">
            <v-card>
                <v-card-title>Aumentar Costos Originales</v-card-title>
                <v-card-text>
                    <v-text-field
                        v-model.number="porcentajeAumento"
                        label="Porcentaje (%)"
                        type="number"
                        required
                    />
                </v-card-text>
                <v-card-actions>
                    <v-btn text @click="dialogoAumento = false">Cancelar</v-btn>
                    <v-btn color="green" text @click="aumentarCostos"
                        >Aplicar</v-btn
                    >
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Diálogo Agregar/Editar Artículo -->
        <v-dialog v-model="dialog" max-width="800px">
            <v-card>
                <v-card-title class="d-flex justify-space-between align-center">
                    {{ isEdit ? "Editar" : "Agregar" }} Artículo
                    <v-btn icon @click="dialog = false">
                        <v-icon color="red">mdi-close</v-icon>
                    </v-btn>
                </v-card-title>
                <v-card-text>
                    <v-form ref="form">
                        <!-- Campos artículo -->
                        <v-text-field
                            v-model="form.nombre"
                            label="Nombre"
                            required
                        />
                        <v-text-field
                            v-model="form.descripcion"
                            label="Descripción"
                        />
                        <v-text-field
                            v-model="form.categoria"
                            label="Categoría"
                        />
                        <v-text-field
                            v-model.number="form.precio_base"
                            label="Precio Base"
                            type="number"
                            required
                        />

                        <!-- Variantes dinámicas -->
                        <div
                            v-for="(v, i) in form.variantes"
                            :key="i"
                            class="mt-6 pa-4 grey lighten-4"
                        >
                            <h3 class="subtitle-1">Variante {{ i + 1 }}</h3>
                            <v-text-field
                                v-model="v.sku"
                                label="SKU"
                                required
                            />
                            <v-text-field
                                v-model.number="v.stock"
                                label="Stock"
                                type="number"
                                required
                            />
                            <v-text-field
                                v-model.number="v.precio"
                                label="Precio Variante"
                                type="number"
                                required
                            />

                            <!-- ← INICIO: selección amigable de atributos -->
                            <div
                                v-for="attr in atributosDefinidos"
                                :key="attr.id"
                                class="mt-2"
                            >
                                <v-select
                                    :label="attr.nombre"
                                    :items="valoresPorAtributo[attr.id] || []"
                                    item-text="valor"
                                    item-value="valor"
                                    v-model="v.atributos[attr.slug]"
                                    dense
                                    clearable
                                />
                            </div>
                            <!-- ← FIN: selección amigable de atributos -->

                            <v-btn
                                text
                                small
                                color="red"
                                @click="removeVariante(i)"
                            >
                                <v-icon left>mdi-delete</v-icon> Eliminar
                                variante
                            </v-btn>
                        </div>
                        <v-btn color="primary" text small @click="addVariante">
                            <v-icon left>mdi-plus</v-icon> Agregar Variante
                        </v-btn>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn text @click="dialog = false">Cancelar</v-btn>
                    <v-btn color="black" text @click="saveArticulo">
                        {{ isEdit ? "Guardar" : "Agregar" }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Diálogo Confirmar Eliminación -->
        <v-dialog v-model="confirmDeleteDialog" max-width="400px">
            <v-card>
                <v-card-title class="headline"
                    >¿Eliminar artículo?</v-card-title
                >
                <v-card-text>
                    ¿Estás seguro de que querés eliminar
                    <strong>{{ articuloAEliminar?.nombre }}</strong
                    >?
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn text @click="confirmDeleteDialog = false"
                        >Cancelar</v-btn
                    >
                    <v-btn color="red" text @click="deleteArticulo"
                        >Eliminar</v-btn
                    >
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Loading Overlay -->
        <v-overlay
            :model-value="loading"
            class="d-flex align-center justify-center"
            persistent
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
import ExcelJS from "exceljs";
import {
    cachedFetch,
    updateCache,
    appendToCache,
    removeFromCache,
    modifyInCache,
} from "@/utils/cacheFetch";
import { notifyCacheChange } from "@/utils/cacheEvents";
import { ARTICULOS_KEY } from "@/utils/cacheKeys";
import { useSyncedCache } from "@/utils/useSyncedCache";

export default {
    data() {
        return {
            loading: false,
            dialog: false,
            dialogoAumento: false,
            confirmDeleteDialog: false,
            isEdit: false,
            articuloAEliminar: null,
            porcentajeAumento: 0,
            search: "",

            // ← NUEVO: para traer atributos definidos y sus valores
            atributosDefinidos: [],
            valoresPorAtributo: {},

            // Formulario de artículo + variantes
            form: {
                id: null,
                nombre: "",
                descripcion: "",
                categoria: "",
                precio_base: 0,
                variantes: [
                    {
                        sku: "",
                        stock: 0,
                        precio: 0,
                        atributos: {}, // ← MODIFICADO: antes atributosJson
                    },
                ],
            },
            articulos: [],
            headers: [
                { title: "Nombre", key: "nombre" },
                { title: "Descripción", key: "descripcion" },
                { title: "Categoría", key: "categoria" },
                { title: "Precio Base", key: "precio_base" },
                {
                    title: "Acciones",
                    key: "actions",
                    align: "center",
                    sortable: false,
                },
            ],
        };
    },

    computed: {
        articulosFiltrados() {
            const term = this.search.toLowerCase().trim();
            return this.articulos.filter(
                (a) =>
                    a.nombre.toLowerCase().includes(term) ||
                    a.categoria.toLowerCase().includes(term)
            );
        },
    },

    mounted() {
        // Sync artículos
        useSyncedCache({
            key: ARTICULOS_KEY,
            apiPath: "/articulos/actualizados-desde",
            fetchFn: () => axios.get("/api/articulos").then((r) => r.data),
            onData: (data) =>
                (this.articulos = Array.isArray(data) ? data : []),
            setLoading: (v) => (this.loading = v),
        });

        // ← NUEVO: Cargar atributos definidos y sus valores
        axios.get("/api/atributos").then((r) => {
            this.atributosDefinidos = r.data;
            r.data.forEach((attr) => {
                axios.get(`/api/atributos/${attr.id}/valores`).then((r2) => {
                    this.$set(this.valoresPorAtributo, attr.id, r2.data);
                });
            });
        });

        window.addEventListener("notifyCacheChange", this.handleCacheSync);
    },

    beforeUnmount() {
        window.removeEventListener("notifyCacheChange", this.handleCacheSync);
    },

    methods: {
        handleCacheSync(e) {
            if (e.detail === ARTICULOS_KEY) this.fetchArticulos();
        },

        addVariante() {
            this.form.variantes.push({
                sku: "",
                stock: 0,
                precio: 0,
                atributos: {}, // ← MODIFICADO
            });
        },
        removeVariante(i) {
            this.form.variantes.splice(i, 1);
        },

        openAddDialog() {
            this.isEdit = false;
            this.form = {
                id: null,
                nombre: "",
                descripcion: "",
                categoria: "",
                precio_base: 0,
                variantes: [
                    { sku: "", stock: 0, precio: 0, atributos: {} }, // ← MODIFICADO
                ],
            };
            this.dialog = true;
        },

        openEditDialog(item) {
            this.isEdit = true;
            this.loading = true;
            axios
                .get(`/api/articulos/${item.id}`)
                .then(({ data }) => {
                    this.form = {
                        id: data.id,
                        nombre: data.nombre,
                        descripcion: data.descripcion,
                        categoria: data.categoria,
                        precio_base: data.precio_base,
                        variantes: data.variantes.map((v) => ({
                            sku: v.sku,
                            stock: v.stock,
                            precio: v.precio,
                            atributos: v.atributos, // ← ahora ya viene objeto
                        })),
                    };
                    this.dialog = true;
                })
                .finally(() => (this.loading = false));
        },

        saveArticulo() {
            if (!this.validateForm()) return;
            this.loading = true;

            // ← MODIFICADO: payload toma directamente v.atributos
            const payload = {
                nombre: this.form.nombre,
                descripcion: this.form.descripcion,
                categoria: this.form.categoria,
                precio_base: this.form.precio_base,
                variantes: this.form.variantes.map((v) => ({
                    sku: v.sku,
                    stock: v.stock,
                    precio: v.precio,
                    atributos: v.atributos,
                })),
            };

            const req = this.isEdit
                ? axios.put(`/api/articulos/${this.form.id}`, payload)
                : axios.post(`/api/articulos`, payload);

            req.then((res) => {
                const art = res.data.articulo || res.data;
                if (this.isEdit) {
                    this.articulos = modifyInCache(ARTICULOS_KEY, (list) =>
                        list.map((a) => (a.id === art.id ? art : a))
                    );
                } else {
                    this.articulos = appendToCache(ARTICULOS_KEY, art);
                }
                notifyCacheChange(ARTICULOS_KEY);
                this.dialog = false;
            })
                .catch((err) => {
                    console.error(err);
                    alert(err.response?.data?.message || "Error al guardar");
                })
                .finally(() => (this.loading = false));
        },

        deleteArticulo() {
            this.loading = true;
            axios
                .delete(`/api/articulos/${this.articuloAEliminar.id}`)
                .then(() => {
                    this.articulos = removeFromCache(
                        ARTICULOS_KEY,
                        (a) => a.id === this.articuloAEliminar.id
                    );
                    notifyCacheChange(ARTICULOS_KEY);
                    this.confirmDeleteDialog = false;
                })
                .finally(() => (this.loading = false));
        },

        fetchArticulos(force = false) {
            this.loading = true;
            cachedFetch(
                ARTICULOS_KEY,
                () => axios.get("/api/articulos").then((r) => r.data),
                { ttl: 86400, forceRefresh: force }
            )
                .then(
                    (data) => (this.articulos = Array.isArray(data) ? data : [])
                )
                .finally(() => (this.loading = false));
        },

        recalcularPrecios() {
            this.loading = true;
            axios
                .put("/api/articulos/recalcular-precios")
                .then((res) => {
                    this.articulos = Array.isArray(res.data.articulos)
                        ? res.data.articulos
                        : [];
                    updateCache(ARTICULOS_KEY, this.articulos);
                    notifyCacheChange(ARTICULOS_KEY);
                    alert("Precios recalculados");
                })
                .finally(() => (this.loading = false));
        },

        abrirDialogoAumento() {
            this.porcentajeAumento = 0;
            this.dialogoAumento = true;
        },
        aumentarCostos() {
            this.loading = true;
            axios
                .put("/api/articulos/aumentar-costos", {
                    porcentaje: this.porcentajeAumento,
                })
                .then((res) => {
                    this.articulos = Array.isArray(res.data.articulos)
                        ? res.data.articulos
                        : [];
                    updateCache(ARTICULOS_KEY, this.articulos);
                    notifyCacheChange(ARTICULOS_KEY);
                    this.dialogoAumento = false;
                    alert("Costos actualizados");
                })
                .finally(() => (this.loading = false));
        },

        async exportarExcel() {
            this.loading = true;
            const workbook = new ExcelJS.Workbook();
            const sheet = workbook.addWorksheet("Artículos");
            sheet.columns = [
                { header: "Nombre", key: "nombre", width: 30 },
                { header: "Descripción", key: "descripcion", width: 40 },
                { header: "Categoría", key: "categoria", width: 20 },
                { header: "Precio Base", key: "precio_base", width: 15 },
            ];
            this.articulosFiltrados.forEach((a) => sheet.addRow(a));
            const buffer = await workbook.xlsx.writeBuffer();
            const link = document.createElement("a");
            link.href = URL.createObjectURL(new Blob([buffer]));
            link.download = "articulos.xlsx";
            link.click();
            this.loading = false;
        },

        validateForm() {
            return (
                this.form.nombre.trim() &&
                !isNaN(this.form.precio_base) &&
                this.form.variantes.every(
                    (v) => v.sku.trim() && !isNaN(v.stock) && !isNaN(v.precio)
                )
            );
        },
    },
};
</script>

<style scoped>
@media (max-width: 768px) {
    h1.title {
        font-size: 24px !important;
        margin-bottom: 16px;
    }
    .v-text-field,
    .v-textarea {
        font-size: 18px;
    }
    .v-btn {
        font-size: 18px;
        min-height: 44px;
    }
    .v-btn .v-icon {
        font-size: 20px;
    }
    .mb-4 .v-col[cols="auto"] {
        width: 100% !important;
        margin-bottom: 12px;
    }
    .v-dialog .v-card-title {
        font-size: 20px;
        font-weight: bold;
    }
    .v-dialog .v-text-field,
    .v-dialog .v-textarea {
        font-size: 18px;
    }
    .v-card-actions .v-btn {
        font-size: 18px;
    }
}
</style>
