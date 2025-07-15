
import React, { useState } from 'react';
import * as XLSX from 'xlsx';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Upload, Download, FileSpreadsheet } from 'lucide-react';
import { processExcelData, generateExcelData, ExcelRow } from '@/utils/excelProcessor';
import { toast } from 'sonner';

const ExcelManager = () => {
  const [data, setData] = useState<ExcelRow[]>([]);
  const [isLoading, setIsLoading] = useState(false);

  const handleFileUpload = (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) return;

    if (!file.name.endsWith('.xlsx') && !file.name.endsWith('.xls')) {
      toast.error('Por favor, selecione apenas arquivos Excel (.xlsx ou .xls)');
      return;
    }

    setIsLoading(true);
    const reader = new FileReader();
    
    reader.onload = (e) => {
      try {
        const data = e.target?.result;
        const workbook = XLSX.read(data, { type: 'binary' });
        const sheetName = workbook.SheetNames[0];
        const worksheet = workbook.Sheets[sheetName];
        const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: ['numero', 'nome'] });
        
        const processedData = processExcelData(jsonData);
        setData(processedData);
        
        toast.success(`Arquivo processado com sucesso! ${processedData.length} registros únicos encontrados.`);
      } catch (error) {
        console.error('Erro ao processar arquivo:', error);
        toast.error('Erro ao processar o arquivo Excel');
      } finally {
        setIsLoading(false);
      }
    };
    
    reader.readAsBinaryString(file);
  };

  const handleDownload = () => {
    if (data.length === 0) {
      toast.error('Não há dados para download');
      return;
    }

    try {
      const excelData = generateExcelData(data);
      const worksheet = XLSX.utils.aoa_to_sheet(excelData);
      const workbook = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(workbook, worksheet, 'Dados Processados');
      
      // Ajustar largura das colunas
      worksheet['!cols'] = [
        { width: 15 }, // Coluna Numero
        { width: 20 }  // Coluna Nome
      ];
      
      XLSX.writeFile(workbook, 'dados_processados.xlsx');
      toast.success('Arquivo baixado com sucesso!');
    } catch (error) {
      console.error('Erro ao baixar arquivo:', error);
      toast.error('Erro ao gerar o arquivo para download');
    }
  };

  const clearData = () => {
    setData([]);
    toast.info('Dados limpos');
  };

  return (
    <div className="container mx-auto p-6 space-y-6">
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <FileSpreadsheet className="h-6 w-6" />
            Gerenciador de Excel
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex flex-col sm:flex-row gap-4">
            <div className="flex-1">
              <Input
                type="file"
                accept=".xlsx,.xls"
                onChange={handleFileUpload}
                disabled={isLoading}
                className="cursor-pointer"
              />
            </div>
            <div className="flex gap-2">
              <Button
                onClick={handleDownload}
                disabled={data.length === 0}
                className="flex items-center gap-2"
              >
                <Download className="h-4 w-4" />
                Download
              </Button>
              <Button
                onClick={clearData}
                variant="outline"
                disabled={data.length === 0}
              >
                Limpar
              </Button>
            </div>
          </div>
          
          {isLoading && (
            <div className="text-center py-4">
              <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
              <p className="mt-2 text-sm text-muted-foreground">Processando arquivo...</p>
            </div>
          )}
        </CardContent>
      </Card>

      {data.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>Dados Processados ({data.length} registros)</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="max-h-96 overflow-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Número</TableHead>
                    <TableHead>Nome</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {data.map((row, index) => (
                    <TableRow key={index}>
                      <TableCell className="font-mono">{row.numero}</TableCell>
                      <TableCell>{row.nome}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          </CardContent>
        </Card>
      )}

      <Card>
        <CardHeader>
          <CardTitle>Instruções</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-2 text-sm text-muted-foreground">
            <p><strong>Formato esperado:</strong> Arquivo Excel com duas colunas (Número e Nome)</p>
            <p><strong>Processamento aplicado:</strong></p>
            <ul className="list-disc list-inside ml-4 space-y-1">
              <li>Adiciona "55" no início de cada número</li>
              <li>Remove o 5º dígito se for "9"</li>
              <li>Mantém apenas o primeiro nome com primeira letra maiúscula</li>
              <li>Remove números duplicados</li>
            </ul>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default ExcelManager;
