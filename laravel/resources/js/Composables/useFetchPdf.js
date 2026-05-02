/**
 * Composable exclusivo para descargas de archivos (PDF, Excel, etc.)
 * Retorna Blob directamente.
 *
 * Puedes modificar el mensaje de error usando setPdfErrorMessage(string).
 */
let pdfErrorMessage = "Error al generar el PDF";

export function setPdfErrorMessage(message) {
    pdfErrorMessage = message || "Error al generar el PDF";
}

export function useFetchPdf() {

    const getXsrfToken = () => {
        const cookie = document.cookie
            .split("; ")
            .find(row => row.startsWith("XSRF-TOKEN="));

        return cookie ? decodeURIComponent(cookie.split("=")[1]) : "";
    };

    const fetchPdf = async (url, body = {}, method = "POST") => {

        // Regenerar CSRF para Laravel
        await fetch("/sanctum/csrf-cookie", {
            credentials: "same-origin",
        });

        const response = await fetch(url, {
            method,
            credentials: "same-origin",
            headers: {
                "Accept": "application/pdf",
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest",
                "X-XSRF-TOKEN": getXsrfToken(),
            },
            body: JSON.stringify(body),
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || pdfErrorMessage);
        }

        return await response.blob();
    };

    return { fetchPdf };
}
