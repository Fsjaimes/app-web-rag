// src/composables/useDateFormatter.js
export function useDateFormatter() {
    const convertDateFormat = (dateString) => {
      // Validar si dateString es undefined, null o vacío
      if (dateString == '' || dateString == null || dateString == undefined) {
        return '';
      }

      // Array de meses en español
      const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

      // Descomponer la fecha
      const [day, monthName, year] = dateString.split(' ');

      // Validar que monthName existe (verificar que la fecha tiene el formato correcto)
      if (!monthName) {
        console.log('Formato de fecha no válido', dateString);
        return '';
      }

      // Eliminar la coma al final del mes si está presente
      const cleanedMonthName = monthName.replace(',', '').trim();

      // Obtener el índice del mes
      const monthIndex = months.indexOf(cleanedMonthName); // Busca el mes en el array y devuelve el índice

      // Asegurarnos de que el mes es válido
      if (monthIndex === -1) {
          console.log('Mes no válido', cleanedMonthName);
          throw new Error('Mes no válido');
      }

      // Formatear la fecha en el formato yyyy-mm-dd
      const formattedDate = `${year}-${(monthIndex + 1).toString().padStart(2, '0')}-${day.padStart(2, '0')}`;
      return formattedDate;
    };

    const displayDateFormat = (dateString) => {
      if (dateString == '' || dateString == null || dateString == undefined) {
        return '';
      }
      // Array de meses en español
      const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    
      // Convertir la cadena de fecha a un objeto Date usando UTC
      const [year, month, day] = dateString.split('-');
      const date = new Date(Date.UTC(year, month - 1, day));  // Meses en JavaScript empiezan desde 0
    
      // Obtener el día, mes y año
      const dayOfMonth = date.getUTCDate();
      const monthName = months[date.getUTCMonth()]; // Obtener el mes usando el índice
      const yearFormatted = date.getUTCFullYear();
    
      // Retornar la fecha en el formato deseado
      return `${dayOfMonth} ${monthName}, ${yearFormatted}`;
    };

    const formatMonthYear = (dateString) => {
      if (!dateString) return '';
      // Array de meses en español en MAYÚSCULAS
      const months = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];

      let year, month;

      if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
        // Formato 'YYYY-MM-DD'
        [year, month] = dateString.split('-');
      } else if (/^\d{4}-\d{2}$/.test(dateString)) {
        // Formato 'YYYY-MM'
        [year, month] = dateString.split('-');
      } else {
        return '';
      }
      // Convertir el mes a índice y a nombre de mes en mayúsculas
      const monthName = months[parseInt(month, 10) - 1] || '';
      if (!monthName) return '';
      return `${monthName} ${year}`;
    };

    return { convertDateFormat, displayDateFormat, formatMonthYear };
  }
  