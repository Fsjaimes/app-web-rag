// resources/js/Composables/useDVValidator.js

/**
 * Composable para:
 *  ✔ Calcular el DV según algoritmo oficial de la DIAN
 *  ✔ Validar si el DV ingresado coincide con el calculado
 *
 * ❗ No incluye reglas de negocio (si el DV es obligatorio o no).
 *    Esa lógica debe hacerse en el componente.
 */

import { useAlert } from "@/Composables/useSweetAlert.js";
const { showWarning } = useAlert();

export function useDVValidator() {

    /**
     * 🔢 calcularDV(nit)
     * Calcula el dígito de verificación DIAN usando módulo 11.
     *
     * @param {string|number} nit
     * @returns {number|null}
     */
    const calcularDV = (nit) => {
        if (!nit || isNaN(nit)) return null;

        // Pesos en el orden de aplicación (desde el último dígito hacia la izquierda)
        const pesos = [3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71];

        const nitStr = nit.toString().trim();
        const len = nitStr.length;

        let suma = 0;
        let pesoIndex = 0;

        // Recorremos de derecha a izquierda, aplicando pesos desde 3 hacia arriba
        for (let i = len - 1; i >= 0; i--) {
            const digito = parseInt(nitStr[i], 10);
            const peso = pesos[pesoIndex];
            suma += digito * peso;
            pesoIndex++;
        }

        const residuo = suma % 11;
        return residuo > 1 ? 11 - residuo : residuo;
    };

    /**
     * 🔍 validarDV(nit, dvIngresado, nombreCampo)
     * Compara el DV calculado con el DV ingresado.
     *
     * @param {string|number} nit
     * @param {string|number|null} dvIngresado
     * @param {string} nombreCampo
     * @returns {boolean}
     */
    const validarDV = (nit, dvIngresado, nombreCampo = "DV") => {
        // Si viene vacío → lo considera válido (la regla se aplica en el componente)
        if (dvIngresado === null || dvIngresado === "" || dvIngresado === undefined) {
            return true;
        }

        const dvCalculado = calcularDV(nit);

        if (parseInt(dvIngresado, 10) !== dvCalculado) {
            showWarning(
                "DV incorrecto",
                `${nombreCampo} no coincide con el número identificación.`
            );
            return false;
        }

        return true;
    };

    /**
     * Misma lógica que validarDV pero sin mostrar alertas (p. ej. marcar errores en formulario).
     * Si dv viene vacío/null → true (la obligatoriedad la valida el formulario).
     */
    const esDvValidoParaNit = (nit, dvIngresado) => {
        if (dvIngresado === null || dvIngresado === "" || dvIngresado === undefined) {
            return true;
        }
        const dvCalculado = calcularDV(nit);
        if (dvCalculado === null) return false;
        return parseInt(dvIngresado, 10) === dvCalculado;
    };

    return {
        calcularDV,
        validarDV,
        esDvValidoParaNit,
    };
}

/**
 * 📘 EJEMPLOS DE USO EN UN COMPONENTE
 *
 * import { useDVValidator } from "@/Composables/useDVValidator";
 * const { calcularDV, validarDV } = useDVValidator();
 *
 * ✔ Calcular el DV:
 * const dv = calcularDV(this.form.identificationNumber);
 *
 * ✔ Validar el DV ingresado:
 * if (!validarDV(this.form.identificationNumber, this.form.dv, "Dígito de Verificación")) {
 *     return; // Detiene submit
 * }
 */
