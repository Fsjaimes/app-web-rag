import { useAlert } from '@/Composables/useSweetAlert.js';
const { showWarning } = useAlert();

const VALID_TLDS = [
    "com", "co", "net", "org", "edu", "gov", "io", "app", "dev", "es",
    "mx", "ar", "cl", "uk", "de", "fr", "it", "us", "info", "biz", "store"
];

const RFC_EMAIL_REGEX =
    /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)+$/;

export function useEmailValidator() {

    const isRFCValid = (email) => {
        const value = String(email ?? '').trim();
        return RFC_EMAIL_REGEX.test(value);
    };
    
    const hasValidTLD = (email) => {
        const value = String(email ?? '').trim().toLowerCase();
        if (!value.includes('.')) return false;
        const tld = value.split('.').pop();
        return VALID_TLDS.includes(tld);
    };

    const isValidEmail = (email) => isRFCValid(email) && hasValidTLD(email);

    const validateEmailWithAlert = (email, fieldName = "correo") => {

        if (!isRFCValid(email)) {
            showWarning(
                `Correo inválido ${fieldName}`,
                `El correo ingresado tiene un formato incorrecto.`
            );
            return false;
        }

        if (!hasValidTLD(email)) {
            showWarning(
                `Dominio inválido ${fieldName}`,
                `El dominio o extensión del correo no es válido.`
            );
            return false;
        }

        return true;
    };

    /**
     * Valida múltiples correos:
     * Recibe un objeto: { campo: valor, campo2: valor2 }
     * Retorna true si TODOS los correos son válidos.
     * Si uno falla → muestra alerta indicando cuál fue.
     */
    const validateMultipleEmails = (emailsObj) => {
        for (const [fieldName, email] of Object.entries(emailsObj)) {

            if (!email || email.trim() === "") continue; // los vacíos los manejas tú en el componente

            if (!isRFCValid(email)) {
                showWarning(
                    `Correo inválido ${fieldName}`,
                    `El formato del correo "${email}" no es válido.`
                );
                return false;
            }

            if (!hasValidTLD(email)) {
                showWarning(
                    `Dominio inválido ${fieldName}`,
                    `La extensión o dominio del correo "${email}" no es válido.`
                );
                return false;
            }
        }
        return true;
    };

    return {
        isRFCValid,
        hasValidTLD,
        isValidEmail,
        validateEmailWithAlert,
        validateMultipleEmails
    };
}

    /**
     * ============================================================
     * 🔹 EJEMPLOS DE USO DESDE UN COMPONENTE VUE
     * ============================================================
     *
     * ------------------------------------------------------------
     * ✅ 1. Validar un solo correo electrónico (casos simples)
     * ------------------------------------------------------------
     * import { useEmailValidator } from "@/Composables/useEmailValidator";
     * const { validateEmailWithAlert } = useEmailValidator();
     *
     * if (!validateEmailWithAlert(this.form.email, "Correo Electrónico")) {
     *     return; // detiene el submit
     * }
     *
     *
     * ------------------------------------------------------------
     * ✅ 2. Validar dos correos independientes
     * Ideal para formularios como creación de terceros (email + billingEmail).
     * ------------------------------------------------------------
     * if (!validateEmailWithAlert(this.form.email, "Correo de Contacto")) return;
     * if (!validateEmailWithAlert(this.form.billingEmail, "Correo de Facturación")) return;
     *
     * // Ambos son válidos → continúa el submit
     *
     *
     * ------------------------------------------------------------
     * ✅ 3. Validar múltiples correos dinámicos (3 o más)
     * Usar cuando el formulario tiene muchos correos o una lista variable.
     * ------------------------------------------------------------
     * import { useEmailValidator } from "@/Composables/useEmailValidator";
     * const { validateMultipleEmails } = useEmailValidator();
     *
     * const correos = {
     *     emailPersonal: this.form.emailPersonal,
     *     emailEmpresa: this.form.emailEmpresa,
     *     emailFacturacion: this.form.emailFacturacion,
     *     emailSoporte: this.form.emailSoporte,
     * };
     *
     * if (!validateMultipleEmails(correos)) {
     *     return; // el método ya muestra cuál falló
     * }
     *
     * // Todos los correos son válidos → continúa
     *
     *
     * ------------------------------------------------------------
     * 🧠 NOTAS IMPORTANTES
     * ------------------------------------------------------------
     * - Este composable **NO valida campos vacíos**, eso se maneja desde el componente.
     * - validateEmailWithAlert() muestra una alerta SweetAlert2 indicando:
     *      → cuál correo falló
     *      → si falló por formato o por dominio
     * - validateMultipleEmails() se recomienda cuando:
     *      → hay 3 o más correos en el formulario
     *      → los correos se generan dinámicamente
     *      → no se conoce de antemano cuántos campos email habrá
     *
     * ============================================================
 */
