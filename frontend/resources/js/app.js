// resources/js/app.js (o donde lo tengas)
import "./bootstrap";
import { createApp } from "vue";
import App from "../../src/app.vue";
import router from "../../src/router";

// Vuetify
import { createVuetify } from "vuetify";
import "vuetify/styles";
import * as components from "vuetify/components";
import * as directives from "vuetify/directives";
import { aliases, mdi } from "vuetify/iconsets/mdi";
import "@mdi/font/css/materialdesignicons.css";
import { es } from "vuetify/locale";

// Axios
import axios from "axios";

// 🔧 Apunta todas las requests a /api
axios.defaults.baseURL = `${import.meta.env.VITE_APP_URL}`;
// Si tu VITE_APP_URL ya incluye /api, sólo pon:
// axios.defaults.baseURL = import.meta.env.VITE_APP_URL;

axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
axios.defaults.headers.common["Accept"] = "application/json";

// 🚀 Recupera y setea el Bearer token
const token = localStorage.getItem("auth_token");
if (token) {
    axios.defaults.headers.common["Authorization"] = `Bearer ${token}`;
}

// 📥 Interceptor para capturar 401 y forzar logout
axios.interceptors.response.use(
    (res) => res,
    (err) => {
        if (err.response?.status === 401) {
            localStorage.removeItem("auth_token");
            delete axios.defaults.headers.common["Authorization"];
            router.push("/login");
        }
        return Promise.reject(err);
    }
);

// Crear Vuetify
const vuetify = createVuetify({
    components,
    directives,
    icons: { defaultSet: "mdi", aliases, sets: { mdi } },
    locale: { locale: "es", messages: { es } },
});

// Registrar componente global
import ResponsiveTable from "@/components/components/ResponsiveTable.vue";

const app = createApp(App);
app.component("ResponsiveTable", ResponsiveTable);
app.use(router).use(vuetify).mount("#app");
