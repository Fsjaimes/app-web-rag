// resources/js/Composables/useAlert.js
import Swal from "sweetalert2";

export function useAlert() {
    /**
     * Muestra un Swal.fire con soporte para:
     * showAlert('success', 'Título', 'Texto', 2500)
     * showAlert('success', 'Título', 'Texto', '', 1500)  // footer vacío, timer 1500 ms
     * showAlert({ icon: 'success', title: 'Título', text: 'Texto', timer: 2500 })
     */

    // Muestra Alerta estándar
    // Soporta: showAlert(icon, title, text, timer), showAlert(icon, title, text, footer, timer), o showAlert({ ... })
    const showAlert = (iconOrOptions, title, text, timer = 2500) => {
        let options = {};

        if (typeof iconOrOptions === "object") {
            // Se pasó un objeto
            options = iconOrOptions;
        } else if (arguments.length >= 5) {
            // 5 argumentos: icon, title, text, footer, timer
            options = {
                icon: iconOrOptions,
                title,
                text,
                footer: arguments[3],
                timer: arguments[4],
            };
        } else {
            // 4 argumentos: icon, title, text, timer
            options = { icon: iconOrOptions, title, text, timer };
        }

        const showConfirmButton = options.showConfirmButton ?? false;
        const rawTimer = options.timer;
        const timerMs = showConfirmButton
            ? undefined
            : (typeof rawTimer === "number" && rawTimer > 0 ? rawTimer : 2500);

        const swalOptions = {
            icon: options.icon || "info",
            title: options.title || "",
            text: options.text || "",
            timer: timerMs,
            showConfirmButton,
            confirmButtonText: options.confirmButtonText || (showConfirmButton ? "OK" : undefined),
            confirmButtonColor: options.confirmButtonColor || (showConfirmButton ? "#3085d6" : undefined),
            timerProgressBar: !!timerMs,
            returnFocus: options.returnFocus ?? true,
        };
        if (options.footer != null) {
            swalOptions.footer = options.footer;
        }
        return Swal.fire(swalOptions);
    };

    // Muestra Alerta de advertencia con botón OK
    const showWarning = (
        title = "¡Atención!",
        text = "Revise la información ingresada.",
        timer = 2500
    ) => {
        return Swal.fire({
            icon: "warning",
            title,
            text,
            confirmButtonText: "OK",
            confirmButtonColor: "#3085d6",
            timer,
            timerProgressBar: true,
        });
    };

    // Muestra Alerta flotante tipo toast
    const showToast = (
        iconOrOptions,
        title,
        position = "top-end",
        timer = 2000
    ) => {
        let options = {};

        if (typeof iconOrOptions === "object") {
            options = iconOrOptions;
        } else {
            options = { icon: iconOrOptions, title, position, timer };
        }

        return Swal.fire({
            icon: options.icon || "info",
            title: options.title || "",
            toast: true,
            position: options.position || "top-end",
            timer: options.timer || 2000,
            showConfirmButton: false,
            timerProgressBar: true,
        });
    };

    // Muestra Alerta de confirmación con botones
    const showConfirm = async (
        icon = "warning",
        title = "¡Alerta!",
        text = "¿Confirma?",
        confirmButtonText = "Sí, continuar"
    ) => {
        const result = await Swal.fire({
            icon,
            title,
            text,
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: confirmButtonText,
            cancelButtonText: "Cancelar",
        });
        return result.isConfirmed; // 👈 devuelve true o false
    };

    // Muestra Alerta de carga/espera
    const showLoading = (title = "Cargando...", text = "Por favor espera") => {
        Swal.fire({
            title,
            text,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });
        return Swal; // devolvemos la instancia para poder cerrarla luego
    };

    return { showAlert, showToast, showConfirm, showLoading, showWarning };
}
