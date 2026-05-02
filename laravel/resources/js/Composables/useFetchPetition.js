/**
 * Composable para realizar peticiones HTTP estandarizadas usando fetch()
 * No lanza alertas ni loaders — sólo retorna la respuesta del servidor.
 *
 * @returns {function} fetchPetition(url, options)
 */
export function useFetchPetition() {
    /**
     * Composable para realizar peticiones HTTP con fetch() y soporte completo para
     * CSRF de Laravel (Sanctum/Fortify).
     *
     * - Regenera el token CSRF automáticamente para POST/PUT/PATCH/DELETE.
     * - Obtiene el token XSRF-TOKEN desde la cookie.
     * - Maneja JSON seguro en respuestas.
     * @param {string} url - Endpoint del backend (ej: '/api/yards')
     * @param {object} options - Configuración: { method, body, headers }
     * @returns {Promise<{ data: any, ok: boolean, status: number|null, error: Error|null }>}
     **/

    // --------------------------------------------------
    // Obtener el token desde la cookie XSRF-TOKEN (NO desde meta)
    // --------------------------------------------------
    const getXsrfToken = () => {
        const cookie = document.cookie
            .split("; ")
            .find(row => row.startsWith("XSRF-TOKEN="));

        return cookie ? decodeURIComponent(cookie.split("=")[1]) : "";
    };

    // --------------------------------------------------
    // Peticiones JSON
    // --------------------------------------------------

    // Algunos servidores (ej: GoDaddy cPanel con mod_security) bloquean PUT/PATCH/DELETE
    // convirtiéndolos en GET. Se usa POST + X-HTTP-Method-Override para evitarlo.
    // Symfony/Laravel soporta este header de forma nativa.
    const OVERRIDE_METHODS = ["PUT", "PATCH", "DELETE"];

    const fetchPetition = async (url, options = {}) => {
        const {
            method = "GET",
            body = null,
            headers = {},
        } = options;

        const upperMethod = method.toUpperCase();
        const needsOverride = OVERRIDE_METHODS.includes(upperMethod);

        // Método efectivo que se envía al servidor
        const effectiveMethod = needsOverride ? "POST" : upperMethod;

        // Métodos que requieren un token CSRF actualizado
        const requiresCsrf = ["POST", "PUT", "PATCH", "DELETE"].includes(upperMethod);

        if (requiresCsrf) {
            await fetch("/sanctum/csrf-cookie", {
                credentials: "same-origin",
            });
        }

        const defaultHeaders = {
            "Accept": "application/json",
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest",
            "X-XSRF-TOKEN": getXsrfToken(),
            // Si hay override, indicarle a Laravel el método real
            ...(needsOverride ? { "X-HTTP-Method-Override": upperMethod } : {}),
        };

        try {
            const response = await fetch(url, {
                method: effectiveMethod,
                headers: { ...defaultHeaders, ...headers },
                credentials: "same-origin", // Necesario para enviar cookies de sesión
                body: body ? JSON.stringify(body) : null,
            });

            const status = response.status;
            const ok = response.ok;

            let data = null;
            try {
                data = await response.json();
            } catch {
                // Respuestas vacías (ej: DELETE 204)
                data = null;
            }

            return { ok, status, data, error: null };

        } catch (error) {
            console.error("❌ Fetch Error:", error);
            return { ok: false, status: null, data: null, error };
        }
    };

    // --------------------------------------------------
    // Subida de archivos (FormData)
    // --------------------------------------------------
    const uploadFile = async (url, formData, method = "POST") => {

        // Regenerar CSRF antes de subir archivo
        await fetch("/sanctum/csrf-cookie", {
            credentials: "same-origin",
        });

        try {
            const response = await fetch(url, {
                method,
                credentials: "same-origin",
                body: formData, // importante: enviar tal cual
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "X-XSRF-TOKEN": getXsrfToken(),
                    // ⚠ NO agregar Content-Type → lo hace el navegador con boundary
                },
            });

            let data = null;
            try {
                data = await response.json();
            } catch {
                data = null;
            }

            return {
                ok: response.ok,
                status: response.status,
                data,
                error: null
            };

        } catch (error) {
            console.error("❌ File Upload Error:", error);
            return { ok: false, status: null, data: null, error };
        }
    };

    return { fetchPetition, uploadFile };
}