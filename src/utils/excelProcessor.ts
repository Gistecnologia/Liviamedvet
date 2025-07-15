
export interface ExcelRow {
  numero: string;
  nome: string;
}

export const processExcelData = (data: any[]): ExcelRow[] => {
  const processedData: ExcelRow[] = [];
  const seenNumbers = new Set<string>();

  data.forEach((row, index) => {
    // Pula a primeira linha se for cabeçalho
    if (index === 0 && (row.numero === 'numero' || row.numero === 'Numero' || row[0] === 'numero')) {
      return;
    }

    let numero = '';
    let nome = '';

    // Processar número
    if (row.numero || row[0]) {
      const originalNumber = (row.numero || row[0]).toString();
      
      // Adicionar 55 no início
      let processedNumber = '55' + originalNumber;
      
      // Remover o quinto dígito se for 9
      const digits = processedNumber.split('');
      if (digits.length >= 5 && digits[4] === '9') {
        digits.splice(4, 1);
      }
      
      numero = digits.join('');
    }

    // Processar nome
    if (row.nome || row[1]) {
      const originalName = (row.nome || row[1]).toString();
      const firstName = originalName.split(' ')[0];
      nome = firstName.charAt(0).toUpperCase() + firstName.slice(1).toLowerCase();
    }

    // Verificar duplicatas e adicionar apenas se o número não existir
    if (numero && nome && !seenNumbers.has(numero)) {
      seenNumbers.add(numero);
      processedData.push({ numero, nome });
    }
  });

  return processedData;
};

export const generateExcelData = (data: ExcelRow[]) => {
  return [
    ['Numero', 'Nome'], // Headers
    ...data.map(row => [row.numero, row.nome])
  ];
};
