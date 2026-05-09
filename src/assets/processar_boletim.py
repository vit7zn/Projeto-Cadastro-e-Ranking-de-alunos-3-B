import pdfplumber
import sys
import json

def extrair_notas_eeep(caminho_pdf):
    notas_extraidas = {}
    
    with pdfplumber.open(caminho_pdf) as pdf:
        # Geralmente as notas estão na primeira página
        pagina = pdf.pages[0]
        tabela = pagina.extract_table()
        
        if tabela:
            for linha in tabela:
                # Exemplo: Se a linha contém 'PORTUGUÊS', pegamos a nota da coluna do 9º ano
                disciplina = str(linha[0]).upper()
                if 'PORTUGUÊS' in disciplina:
                    notas_extraidas['portugues'] = linha[4] # Coluna do 9º ano
                elif 'MATEMÁTICA' in disciplina:
                    notas_extraidas['matematica'] = linha[4]
                # Adicione as outras disciplinas aqui...

    return notas_extraidas

if __name__ == "__main__":
    # Recebe o caminho do PDF enviado pelo PHP
    caminho = sys.argv[1]
    resultado = extrair_notas_eeep(caminho)
    
    # Retorna o resultado em JSON para o PHP ler
    print(json.dumps(resultado))