/**
 * Reglas de negocio compartidas del formulario de terceros (régimen tributario, tipo contribuyente).
 * Sin efectos secundarios salvo las funciones que reciben callbacks explícitos.
 */

export const TAX_REGIME_WARNING_SPECIAL_EXCLUSIVE =
    'Si selecciona "No responsable" o "Régimen simple de tributación", no puede elegir más tipos de régimen.';

export const TAX_REGIME_WARNING_OTHERS_WITH_SPECIAL =
    'Con otros tipos de régimen seleccionados, no puede incluir "No responsable" ni "Régimen simple de tributación".';

    //obtiene los ids de los regimenes especiales
export function getSpecialTaxRegimeIds(taxRegimeTypes) {
    let noResponsableId = null;
    let regimenSimpleId = null;

    for (const regime of taxRegimeTypes || []) {
        const normalized = (regime?.description || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();

        if (normalized === 'no responsable') noResponsableId = regime.id;
        if (normalized === 'regimen simple de tributacion') regimenSimpleId = regime.id;
    }

    return { noResponsableId, regimenSimpleId };
}

//obtiene los ids de los regimenes especiales
export function getSpecialTaxRegimeIdNumbers(taxRegimeTypes) {
    const { noResponsableId, regimenSimpleId } = getSpecialTaxRegimeIds(taxRegimeTypes);
    return [Number(noResponsableId), Number(regimenSimpleId)].filter(Number.isFinite);
}

//convierte el valor a un array
export function toTaxRegimeTypeIdArray(raw) {
    if (Array.isArray(raw)) {
        return [...raw];
    }
    if (raw === '' || raw === null || raw === undefined) {
        return [];
    }
    return [raw];
}

//obtiene los ids de los regimenes especiales deshabilitados
export function getDisabledTaxRegimeTypeIds(taxRegimeTypes, taxRegimeTypeId) {
    const selected = toTaxRegimeTypeIdArray(taxRegimeTypeId);
    const selectedSet = new Set(selected.map(id => Number(id)));
    const specialIds = getSpecialTaxRegimeIdNumbers(taxRegimeTypes);

    const hasSpecialSelected = specialIds.some(id => selectedSet.has(id));
    const hasOtherSelected = selected.some(id => !specialIds.includes(Number(id)));

    if (hasSpecialSelected) {
        return (taxRegimeTypes || [])
            .map(opt => Number(opt.id))
            .filter(id => !selectedSet.has(id));
    }

    if (hasOtherSelected) {
        return specialIds;
    }

    return [];
}

//resuelve el tipo de contribuyente por el tipo de identificación
export function resolveTaxpayerTypeByIdentification(identificationTypeId, taxpayerTypes) {
    if (!identificationTypeId) {
        return { label: '', id: null };
    }
    const list = taxpayerTypes || [];
    if (Number(identificationTypeId) === 6) {
        const pj = list.find(t => (t.description || '').toLowerCase().includes('jurídica'));
        return {
            label: pj?.description ?? 'Persona Jurídica',
            id: pj?.id ?? null,
        };
    }
    const pn = list.find(t => (t.description || '').toLowerCase().includes('natural'));
    return {
        label: pn?.description ?? 'Persona Natural',
        id: pn?.id ?? null,
    };
}

//corregir la selección de regímenes según las reglas de negocio
export function applyTaxRegimeSelectionRules(newValue, taxRegimeTypes) {
    const selected = toTaxRegimeTypeIdArray(newValue);
    const selectedNumbers = selected.map(id => Number(id));
    const specialIds = getSpecialTaxRegimeIdNumbers(taxRegimeTypes);

    const selectedSpecial = selectedNumbers.filter(id => specialIds.includes(id));
    const selectedOthers = selectedNumbers.filter(id => !specialIds.includes(id));

    if (selectedSpecial.length > 0) {
        const keptSpecial = selected.find(id => specialIds.includes(Number(id)));
        if (selected.length > 1 && keptSpecial !== undefined) {
            return {
                nextValue: [keptSpecial],
                warningMessage: TAX_REGIME_WARNING_SPECIAL_EXCLUSIVE,
            };
        }
    }

    if (selectedOthers.length > 0 && selectedSpecial.length > 0) {
        const filtered = selected.filter(id => !specialIds.includes(Number(id)));
        return {
            nextValue: filtered,
            warningMessage: TAX_REGIME_WARNING_OTHERS_WITH_SPECIAL,
        };
    }

    return { nextValue: null, warningMessage: null };
}

//validación para envío (sin mutar el formulario)
export function validateTaxRegimeTypeCombination(formTaxRegimeTypeId, taxRegimeTypes) {
    const selected = toTaxRegimeTypeIdArray(formTaxRegimeTypeId);
    const specialIds = getSpecialTaxRegimeIdNumbers(taxRegimeTypes);
    const nums = selected.map(Number);
    const selectedSpecial = nums.filter(id => specialIds.includes(id));
    const selectedOthers = nums.filter(id => !specialIds.includes(id));

    if (selectedSpecial.length > 0 && selected.length > 1) {
        return { valid: false, message: TAX_REGIME_WARNING_SPECIAL_EXCLUSIVE };
    }
    if (selectedOthers.length > 0 && selectedSpecial.length > 0) {
        return { valid: false, message: TAX_REGIME_WARNING_OTHERS_WITH_SPECIAL };
    }

    return { valid: true, message: null };
}
