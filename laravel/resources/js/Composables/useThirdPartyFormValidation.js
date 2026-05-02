import { useDVValidator } from '@/Composables/useDVValidator';
import { useEmailValidator } from '@/Composables/useEmailValidator';
import { useFetchPetition } from '@/Composables/useFetchPetition.js';
import { useAlert } from '@/Composables/useSweetAlert.js';
import { validateTaxRegimeTypeCombination, toTaxRegimeTypeIdArray } from '@/Composables/useThirdPartyFormRules.js';

const { validarDV } = useDVValidator();
const { validateEmailWithAlert } = useEmailValidator();
const { fetchPetition } = useFetchPetition();
const { showWarning } = useAlert();

/**
 * Comprueba en servidor si el número de identificación ya existe.
 * @returns {Promise<boolean>} true si ya existe
 */
export async function checkThirdPartyIdentificationExists(identificationNumber, thirdPartyId) {
    if (!identificationNumber || String(identificationNumber).trim() === '') {
        return false;
    }
    try {
        const response = await fetchPetition('/third-parties/validate-identification-number', {
            method: 'POST',
            body: [identificationNumber, thirdPartyId ?? null],
        });
        return !!response.data?.exists;
    } catch (error) {
        console.error('Error validando número de identificación:', error);
        return false;
    }
}

/**
 * Validación completa del formulario de tercero (muta `form` como el flujo original: DV, región/ciudad, etc.).
 */
export async function validateThirdPartyForm(form, options = {}) {
    const taxRegimeTypes = options.taxRegimeTypes ?? [];
    const errors = {};
    let isValid = true;

    const existsYet = await checkThirdPartyIdentificationExists(
        form.identificationNumber,
        form.id ?? null,
    );
    if (existsYet) {
        await showWarning('¡Alerta!', 'El número de identificación ya existe en el sistema.');
        isValid = false;
    }

    if (!form.identificationTypeId || String(form.identificationTypeId).trim() === '') {
        errors.identificationTypeId = 'Tipo identificación no puede estar vacío';
        isValid = false;
    }
    if (form.identificationNumber == '') {
        errors.identificationNumber = 'Número identificación no puede estar vacío';
        isValid = false;
    }
    if (form.identificationTypeId == 6) {
        if (!validarDV(form.identificationNumber, form.dv, 'Dígito de Verificación')) {
            errors.dv = 'Dígito de verificación no es válido';
            isValid = false;
        }
    } else if (form.identificationTypeId == 3) {
        if (!form.dv || form.dv.trim() == '') {
            form.dv = null;
        }
        if (!validarDV(form.identificationNumber, form.dv, 'Dígito de Verificación')) {
            errors.dv = 'Dígito de verificación no es válido';
            isValid = false;
        }
        form.companyName = null;
    } else {
        form.dv = null;
        form.companyName = null;
    }
    if (form.identificationTypeId == 6) {
        if (!form.dv || String(form.dv).trim() === '') {
            errors.dv = 'Dígito de verificación no puede estar vacío';
            isValid = false;
        }
        if (!form.companyName || String(form.companyName).trim() === '') {
            errors.companyName = 'Razón social no puede estar vacía';
            isValid = false;
        }
    }
    if (form.identificationTypeId != 6) {
        if (!form.firstName || String(form.firstName).trim() === '') {
            errors.firstName = 'Primer nombre no puede estar vacío';
            isValid = false;
        }
        if (!form.firstLastName || String(form.firstLastName).trim() === '') {
            errors.firstLastName = 'Primer apellido no puede estar vacío';
            isValid = false;
        }
    }
    if (!form.issuanceCityId || String(form.issuanceCityId).trim() === '') {
        errors.issuanceCityId = 'Lugar de expedición no puede estar vacío';
        isValid = false;
    }
    if (!form.countryId || String(form.countryId).trim() === '') {
        errors.countryId = 'Seleccione un país';
        isValid = false;
    }
    if (form.countryId == '42' || String(form.countryId).trim() === '42') {
        if (!form.regionId || String(form.regionId).trim() === '') {
            errors.regionId = 'Seleccione un departamento';
            isValid = false;
        }
        if (!form.cityId || String(form.cityId).trim() === '') {
            errors.cityId = 'Seleccione una ciudad';
            isValid = false;
        }
    } else {
        form.regionId = null;
        form.cityId = null;
    }
    if (!form.address || String(form.address).trim() === '') {
        errors.address = 'Dirección no puede estar vacía';
        isValid = false;
    }
    if (!form.email || String(form.email).trim() === '') {
        errors.email = 'Correo electrónico no puede estar vacío';
        isValid = false;
    }
    if (!validateEmailWithAlert(form.email, 'Correo Electrónico')) isValid = false;
    if (form.phoneNumber == '') {
        errors.phoneNumber = 'Teléfono 1 no puede estar vacío';
        isValid = false;
    }
    if (!form.thirdPartyTypesId || form.thirdPartyTypesId.length == 0) {
        errors.thirdPartyTypesId = 'Debe seleccionar al menos un tipo de tercero';
        isValid = false;
    }
    const taxRegimeSelected = toTaxRegimeTypeIdArray(form.taxRegimeTypeId);
    if (taxRegimeSelected.length === 0) {
        errors.taxRegimeTypeId = 'Tipo régimen no puede estar vacío';
        isValid = false;
    } else if (taxRegimeTypes.length > 0) {
        const regimeCombo = validateTaxRegimeTypeCombination(form.taxRegimeTypeId, taxRegimeTypes);
        if (!regimeCombo.valid) {
            errors.taxRegimeTypeId = regimeCombo.message ?? 'Combinación de regímenes no válida';
            isValid = false;
        }
    }
    if (!form.taxpayerTypeId || String(form.taxpayerTypeId).trim() === '') {
        errors.taxpayerTypeId = 'Tipo contribuyente no puede estar vacío';
        isValid = false;
    }
    if (!form.legislationTypeId || String(form.legislationTypeId).trim() === '') {
        errors.legislationTypeId = 'Tipo legislación no puede estar vacío';
        isValid = false;
    }
    const vat = form.vatResponsibility;
    if (vat === '' || vat === null || vat === undefined || vat === '0') {
        errors.vatResponsibility = 'Seleccione responsabilidad IVA';
        isValid = false;
    }
    if (!form.billingEmail || String(form.billingEmail).trim() === '') {
        errors.billingEmail = 'Correo facturación no puede estar vacío';
        isValid = false;
    }
    if (!validateEmailWithAlert(form.billingEmail, 'Correo de Facturación')) isValid = false;

    if (form.contactPhoneNumber == '' || form.contactPhoneNumber == null) {
        form.contactPhoneCode = null;
    }

    return {
        isValid,
        errors,
    };
}
