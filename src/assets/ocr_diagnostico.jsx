import { useState, useCallback } from "react";

const DISCIPLINAS_LABELS = {
  portugues:  "Língua Portuguesa",
  matematica: "Matemática",
  ciencias:   "Ciências",
  historia:   "História",
  geografia:  "Geografia",
  ingles:     "Inglês",
  edfisica:   "Ed. Física",
  artes:      "Artes",
  religiao:   "Ens. Religioso",
  filosofia:  "Filosofia",
};

const ANOS_LABELS = {
  ano6: "6º Ano",
  ano7: "7º Ano",
  ano8: "8º Ano",
  ano9: "9º Ano",
};

const LAYOUT_LABELS = {
  A: "Horizontal (disciplinas em linhas, anos em colunas)",
  B: "Vertical/Compacto (anos em linhas, disciplinas em colunas)",
  C: "Bimestral (com cálculo de média)",
  D: "Colunas duplas (Nota + CH por ano)",
};

const EXPECTED = {
  // ── Gabaritos extraídos manualmente dos boletins ──
  "ARARENDÁ": {
    ano6: { portugues:10, artes:8,  edfisica:8,  historia:10, geografia:9,  religiao:10, ciencias:10, matematica:8,  ingles:8  },
    ano7: { portugues:9,  artes:8,  edfisica:9,  historia:8,  geografia:8,  religiao:9,  ciencias:9,  matematica:8,  ingles:9  },
    ano8: { portugues:9,  artes:10, edfisica:9,  historia:8,  geografia:8,  religiao:8,  ciencias:9,  matematica:8,  ingles:10 },
    ano9: { portugues:9,  artes:10, edfisica:9,  historia:8,  geografia:9,  religiao:8,  ciencias:9,  matematica:10, ingles:9  },
  },
  "GONÇALO FURTADO": {
    ano6: { portugues:9.6, matematica:9.7, historia:10,  geografia:10,  ciencias:9.9, artes:10,  edfisica:10,  religiao:10,  ingles:9.8 },
    ano7: { portugues:10,  matematica:10,  historia:10,  geografia:10,  ciencias:9.8, artes:10,  edfisica:9.9, religiao:10,  ingles:9.8 },
    ano8: { portugues:9.9, matematica:9.8, historia:9.8, geografia:9.9, ciencias:10,  artes:9.9, edfisica:10,  religiao:9.8, ingles:9.9 },
    ano9: { portugues:10,  matematica:10,  historia:10,  geografia:9.9, ciencias:10,  artes:10,  edfisica:10,  religiao:10,  ingles:10  },
  },
  // ── Ipaporanga — gabarito lido do boletim real (colunas 2022→6º ... 2025→9º) ──
  "IPAPORANGA": {
    //         port  mat   cien  hist  geo   arte  edfis  rel   ingles
    ano6: { portugues:8,  matematica:9,  ciencias:9,  historia:9,  geografia:9,  artes:9,  edfisica:9,  religiao:9,  ingles:9  },
    ano7: { portugues:9,  matematica:9,  ciencias:10, historia:10, geografia:10, artes:9,  edfisica:9,  religiao:9,  ingles:9  },
    ano8: { portugues:10, matematica:10, ciencias:10, historia:10, geografia:10, artes:10, edfisica:9,  religiao:10, ingles:10 },
    ano9: { portugues:10, matematica:10, ciencias:10, historia:10, geografia:10, artes:10, edfisica:10, religiao:10, ingles:10 },
  },
  // ── Carlota Colares (Crateús) ──
  "CARLOTA COLARES": {
    ano6: { portugues:8, artes:9,  edfisica:8,  historia:8,  geografia:8,  religiao:9,  ciencias:8,  matematica:7, ingles:8  },
    ano7: { portugues:8, artes:9,  edfisica:8,  historia:9,  geografia:8,  religiao:9,  ciencias:8,  matematica:7, ingles:9  },
    ano8: { portugues:8, artes:9,  edfisica:9,  historia:9,  geografia:8,  religiao:9,  ciencias:9,  matematica:7, ingles:8  },
    ano9: { portugues:9, artes:9,  edfisica:8,  historia:9,  geografia:10, religiao:10, ciencias:10, matematica:9, ingles:9  },
  },
  // ── Claudimiro Alves Feitosa (Buriti dos Montes - PI) ──
  "CLAUDIMIRO": {
    ano6: { portugues:10, matematica:9.2, historia:8.3, geografia:9.6, ciencias:9.5, artes:8.9, edfisica:8, religiao:9.6, ingles:9.3 },
    ano7: { portugues:10, matematica:10,  historia:10,  geografia:10,  ciencias:9.8, artes:10,  edfisica:9.9, religiao:10,  ingles:9.8 },
    ano8: { portugues:9.5,matematica:9.5, historia:8.6, geografia:9.4, ciencias:8.1, artes:9.6, edfisica:9.2, religiao:9.9, ingles:8.5 },
    ano9: { portugues:10, matematica:10,  historia:9.6, geografia:10,  ciencias:8.5, artes:9.8, edfisica:10,  religiao:10,  ingles:10  },
  },
  // ── Colégio Vitória (Crateús) ──
  "VITÓRIA": {
    ano6: { portugues:8.9, artes:8.9, historia:9.3, geografia:9.5, religiao:9.1, matematica:9.9, ingles:9.0 },
    ano7: { portugues:9.2, artes:9.9, historia:9.4, geografia:9.1, religiao:9.3, matematica:9.2, ingles:8.9 },
    ano8: { portugues:9.6, artes:9.6, historia:10,  geografia:10,  religiao:9.8, matematica:10,  ingles:10  },
    ano9: { portugues:8.6, artes:10,  historia:10,  geografia:10,  religiao:10,  matematica:9.0, ingles:10  },
  },
  // ── Coração de Maria (Independência) ──
  "CORAÇÃO DE MARIA": {
    ano6: { portugues:10, matematica:10, historia:10, geografia:9.5, ciencias:10,  artes:10,  edfisica:9,  religiao:8.5, ingles:9   },
    ano7: { portugues:9,  matematica:9.8,historia:10, geografia:8.6, ciencias:10,  artes:9,   edfisica:8,  religiao:9.4, ingles:9   },
    ano8: { portugues:9.3,matematica:9.7,historia:9,  geografia:9.7, ciencias:10,  artes:9.5, edfisica:9,  religiao:9.2, ingles:8.5 },
    ano9: { portugues:9.8,matematica:10, historia:9.7,geografia:9.3, ciencias:10,  artes:10,  edfisica:9.7,religiao:9.4, ingles:9.8 },
  },
  // ── Expedito Mendes Chaves (Tamboril) ──
  "EXPEDITO MENDES": {
    ano6: { portugues:8, artes:8, edfisica:9.1, historia:8, geografia:6.8, religiao:8.8, ciencias:6.8, matematica:9, ingles:7.8 },
    ano7: { portugues:8, artes:8, edfisica:8,   historia:9, geografia:9,   religiao:9,   ciencias:9,   matematica:8, ingles:9   },
    ano8: { portugues:8, artes:9, edfisica:8,   historia:7, geografia:9,   religiao:8,   ciencias:9,   matematica:7, ingles:6   },
    ano9: { portugues:9, artes:10,edfisica:8,   historia:8, geografia:9,   religiao:8,   ciencias:9,   matematica:10,ingles:10  },
  },
  // ── Imaculada Conceição (Crateús) ──
  "IMACULADA": {
    ano6: { portugues:9, artes:10, edfisica:10, historia:10, geografia:9,  religiao:9,  ciencias:9,  matematica:9, ingles:10 },
    ano7: { portugues:9, artes:10, edfisica:10, historia:9,  geografia:10, religiao:10, ciencias:10, matematica:9, ingles:10 },
    ano8: { portugues:10,artes:10, edfisica:10, historia:10, geografia:10, religiao:10, ciencias:10, matematica:9, ingles:10 },
    ano9: { portugues:10,artes:10, edfisica:10, historia:10, geografia:10, religiao:10, ciencias:10, matematica:10,ingles:10 },
  },
  // ── Primeiro de Janeiro (Crateús - privado) ──
  "PRIMEIRO DE JANEIRO": {
    ano6: { portugues:7.3, matematica:7.3, historia:8.6, geografia:8.6, ciencias:8.3, edfisica:8.1, ingles:7.5 },
    ano7: { portugues:8,   matematica:7.9, historia:9.1, geografia:8.2, ciencias:9.6, edfisica:8.1, ingles:8.9 },
    ano8: { portugues:8.1, matematica:7.7, historia:9.7, geografia:8.9, ciencias:8.3, edfisica:8.3, ingles:9.2 },
    ano9: { portugues:8.5, matematica:8.3, historia:9.1, geografia:9.3, ciencias:8.5, edfisica:8.1, ingles:8.0 },
  },
  // ── Sônia Burgos (Crateús - privado) ──
  "SÔNIA BURGOS": {
    ano6: { portugues:10, matematica:10, historia:10, geografia:10, ciencias:10, artes:10, edfisica:9,  religiao:10, ingles:10 },
    ano7: { portugues:10, matematica:9,  historia:10, geografia:10, ciencias:10, artes:10, edfisica:9,  religiao:8,  ingles:10 },
    ano8: { portugues:10, matematica:10, historia:9,  geografia:10, ciencias:10, artes:10, edfisica:9,  religiao:10, ingles:10 },
    ano9: { portugues:9.5,matematica:9,  historia:9,  geografia:9,  ciencias:10, artes:10, edfisica:9.5,religiao:9,  ingles:10 },
  },
};

function NotaCell({ nota, esperada }) {
  if (nota === undefined && esperada === undefined) return <td style={{background:"var(--color-background-secondary)", color:"var(--color-text-tertiary)", textAlign:"center", padding:"6px 4px", fontSize:"12px"}}>—</td>;
  
  const diff = esperada !== undefined && nota !== undefined ? Math.abs(nota - esperada) : null;
  const ok = diff !== null && diff < 0.15;
  const falta = nota === undefined && esperada !== undefined;
  const extra = nota !== undefined && esperada === undefined;

  const bg = nota === undefined ? "var(--color-background-tertiary)" :
             falta ? "#fff3cd" :
             ok ? "var(--color-background-success)" :
             extra ? "var(--color-background-info)" :
             "var(--color-background-danger)";
  
  const cor = nota === undefined ? "var(--color-text-tertiary)" :
              falta ? "#856404" :
              ok ? "var(--color-text-success)" :
              extra ? "var(--color-text-info)" :
              "var(--color-text-danger)";

  return (
    <td style={{background:bg, color:cor, textAlign:"center", padding:"6px 4px", fontSize:"13px", fontWeight:"500", border:"1px solid var(--color-border-tertiary)", minWidth:"52px"}}>
      {nota !== undefined ? nota.toFixed(1) : "—"}
      {esperada !== undefined && !ok && nota !== undefined && (
        <div style={{fontSize:"10px", opacity:0.7}}>esp:{esperada}</div>
      )}
    </td>
  );
}

function TabelaResultado({ dados, esperado, escola }) {
  if (!dados || !dados.anos) return null;

  const todosAnos = Object.keys(ANOS_LABELS);
  const todasDisc = Object.keys(DISCIPLINAS_LABELS);

  const anosPresentes = todosAnos.filter(a => dados.anos[a]);
  const discPresentes = todasDisc.filter(d =>
    anosPresentes.some(a => dados.anos[a]?.disciplinas?.[d] !== undefined)
  );

  if (discPresentes.length === 0) return <p style={{color:"var(--color-text-danger)"}}>Nenhuma disciplina encontrada.</p>;

  let totalCorrecto = 0, totalComparavel = 0, totalExtras = 0, totalFaltando = 0;

  anosPresentes.forEach(a => {
    if (!esperado?.[a]) return;
    discPresentes.forEach(d => {
      const got = dados.anos[a]?.disciplinas?.[d];
      const exp = esperado[a]?.[d];
      if (exp !== undefined) {
        totalComparavel++;
        if (got !== undefined && Math.abs(got - exp) < 0.15) totalCorrecto++;
        else if (got === undefined) totalFaltando++;
      } else if (got !== undefined) {
        totalExtras++;
      }
    });
  });

  const pct = totalComparavel > 0 ? Math.round(totalCorrecto / totalComparavel * 100) : null;

  return (
    <div style={{marginTop:"16px"}}>
      <div style={{display:"flex", gap:"12px", flexWrap:"wrap", marginBottom:"10px", alignItems:"center"}}>
        <span style={{background:"var(--color-background-secondary)", borderRadius:"6px", padding:"4px 10px", fontSize:"12px", color:"var(--color-text-secondary)"}}>
          Layout OCR: <b style={{color:"var(--color-text-primary)"}}>{LAYOUT_LABELS[dados.layout_ocr] || dados.layout_ocr || "N/A"}</b>
        </span>
        <span style={{background:"var(--color-background-secondary)", borderRadius:"6px", padding:"4px 10px", fontSize:"12px", color:"var(--color-text-secondary)"}}>
          Fonte: <b style={{color:"var(--color-text-primary)"}}>{dados.fonte}</b>
        </span>
        {pct !== null && (
          <span style={{background: pct>=90?"var(--color-background-success)": pct>=70?"#fff3cd":"var(--color-background-danger)", borderRadius:"6px", padding:"4px 10px", fontSize:"12px", fontWeight:"600", color: pct>=90?"var(--color-text-success)": pct>=70?"#856404":"var(--color-text-danger)"}}>
            ✓ {pct}% precisão ({totalCorrecto}/{totalComparavel} notas corretas)
            {totalFaltando > 0 && ` | ${totalFaltando} faltando`}
          </span>
        )}
      </div>

      <div style={{overflowX:"auto"}}>
        <table style={{borderCollapse:"collapse", width:"100%", fontSize:"13px"}}>
          <thead>
            <tr>
              <th style={{textAlign:"left", padding:"8px 10px", background:"var(--color-background-tertiary)", color:"var(--color-text-secondary)", fontWeight:"500", border:"1px solid var(--color-border-tertiary)", minWidth:"140px"}}>
                Disciplina
              </th>
              {anosPresentes.map(a => (
                <th key={a} style={{textAlign:"center", padding:"8px 6px", background:"var(--color-background-tertiary)", color:"var(--color-text-secondary)", fontWeight:"500", border:"1px solid var(--color-border-tertiary)"}}>
                  {ANOS_LABELS[a]}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {discPresentes.map((d, i) => (
              <tr key={d} style={{background: i%2===0 ? "var(--color-background-primary)" : "var(--color-background-secondary)"}}>
                <td style={{padding:"7px 10px", color:"var(--color-text-primary)", fontWeight:"400", border:"1px solid var(--color-border-tertiary)", fontSize:"13px"}}>
                  {DISCIPLINAS_LABELS[d]}
                </td>
                {anosPresentes.map(a => (
                  <NotaCell
                    key={a}
                    nota={dados.anos[a]?.disciplinas?.[d]}
                    esperada={esperado?.[a]?.[d]}
                  />
                ))}
              </tr>
            ))}
            <tr style={{background:"var(--color-background-tertiary)"}}>
              <td style={{padding:"7px 10px", color:"var(--color-text-secondary)", fontWeight:"500", border:"1px solid var(--color-border-tertiary)", fontSize:"12px"}}>
                Média calculada
              </td>
              {anosPresentes.map(a => (
                <td key={a} style={{textAlign:"center", padding:"7px 4px", fontWeight:"600", color:"var(--color-text-primary)", border:"1px solid var(--color-border-tertiary)", fontSize:"13px"}}>
                  {dados.anos[a]?.media_calculada?.toFixed(2) ?? "—"}
                </td>
              ))}
            </tr>
            <tr style={{background:"var(--color-background-tertiary)"}}>
              <td style={{padding:"7px 10px", color:"var(--color-text-secondary)", fontWeight:"500", border:"1px solid var(--color-border-tertiary)", fontSize:"12px"}}>
                Disciplinas lidas
              </td>
              {anosPresentes.map(a => (
                <td key={a} style={{textAlign:"center", padding:"7px 4px", color:"var(--color-text-secondary)", border:"1px solid var(--color-border-tertiary)", fontSize:"12px"}}>
                  {dados.anos[a]?.quantidade ?? "—"}
                </td>
              ))}
            </tr>
          </tbody>
        </table>
      </div>

      {pct !== null && (
        <div style={{marginTop:"10px", fontSize:"12px", color:"var(--color-text-tertiary)"}}>
          <span style={{marginRight:"16px"}}>🟢 Verde = correto</span>
          <span style={{marginRight:"16px"}}>🔴 Vermelho = divergência</span>
          <span style={{marginRight:"16px"}}>⚫ Cinza = sem gabarito</span>
        </div>
      )}
    </div>
  );
}

export default function OCRDiagnostico() {
  const [arquivo, setArquivo] = useState(null);
  const [preview, setPreview] = useState(null);
  const [resultado, setResultado] = useState(null);
  const [erro, setErro] = useState(null);
  const [carregando, setCarregando] = useState(false);
  const [paginaAtual, setPaginaAtual] = useState(1);
  const [totalPaginas, setTotalPaginas] = useState(1);
  const [escola, setEscola] = useState("");
  const [proxyUrl, setProxyUrl] = useState("ocr_proxy.php");
  const [imagemBase64, setImagemBase64] = useState(null);
  const [imagemTipo, setImagemTipo] = useState(null);
  const [arquivoNome, setArquivoNome] = useState("");
  const [histTests, setHistTests] = useState([]);

  const processarArquivo = useCallback(async (file) => {
    if (!file) return;
    setArquivoNome(file.name);
    setResultado(null);
    setErro(null);
    setPaginaAtual(1);

    const nomeUpper = file.name.toUpperCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    if      (nomeUpper.includes("ARAR"))       setEscola("ARARENDÁ");
    else if (nomeUpper.includes("GONCALO") || nomeUpper.includes("GONÇALO")) setEscola("GONÇALO FURTADO");
    else if (nomeUpper.includes("IPAPOR"))     setEscola("IPAPORANGA");
    else if (nomeUpper.includes("CARLOTA"))    setEscola("CARLOTA COLARES");
    else if (nomeUpper.includes("CLAUDIMIRO")) setEscola("CLAUDIMIRO");
    else if (nomeUpper.includes("VITORIA") || nomeUpper.includes("VITÓRIA")) setEscola("VITÓRIA");
    else if (nomeUpper.includes("CORACAO") || nomeUpper.includes("CORAÇÃO")) setEscola("CORAÇÃO DE MARIA");
    else if (nomeUpper.includes("EXPEDITO"))   setEscola("EXPEDITO MENDES");
    else if (nomeUpper.includes("IMACULADA"))  setEscola("IMACULADA");
    else if (nomeUpper.includes("JANEIRO"))    setEscola("PRIMEIRO DE JANEIRO");
    else if (nomeUpper.includes("SONIA") || nomeUpper.includes("SÔNIA")) setEscola("SÔNIA BURGOS");
    else setEscola("");

    if (file.type === "application/pdf") {
      await processarPDF(file);
    } else if (file.type.startsWith("image/")) {
      await processarImagem(file);
    } else {
      setErro("Formato não suportado. Use PDF, JPG ou PNG.");
    }
  }, []);

  const processarImagem = async (file) => {
    const reader = new FileReader();
    reader.onload = (e) => {
      const base64 = e.target.result.split(",")[1];
      setImagemBase64(base64);
      setImagemTipo(file.type);
      setPreview(e.target.result);
      setTotalPaginas(1);
    };
    reader.readAsDataURL(file);
  };

  const processarPDF = async (file) => {
    try {
      const pdfjsLib = window["pdfjs-dist/build/pdf"];
      if (!pdfjsLib) {
        setErro("PDF.js não carregado. Inclua a biblioteca PDF.js na sua página.");
        return;
      }
      pdfjsLib.GlobalWorkerOptions.workerSrc =
        "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js";

      const arrayBuffer = await file.arrayBuffer();
      const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
      setTotalPaginas(pdf.numPages);
      await renderizarPagina(pdf, 1);
      setArquivo({ pdf, file });
    } catch (e) {
      setErro("Erro ao processar PDF: " + e.message);
    }
  };

  const renderizarPagina = async (pdfObj, num) => {
    const page = await pdfObj.getPage(num);
    const viewport = page.getViewport({ scale: 2.5 });
    const canvas = document.createElement("canvas");
    canvas.width = viewport.width;
    canvas.height = viewport.height;
    const ctx = canvas.getContext("2d");
    await page.render({ canvasContext: ctx, viewport }).promise;
    const dataUrl = canvas.toDataURL("image/jpeg", 0.92);
    const base64 = dataUrl.split(",")[1];
    setImagemBase64(base64);
    setImagemTipo("image/jpeg");
    setPreview(dataUrl);
    setPaginaAtual(num);
  };

  const mudarPagina = async (nova) => {
    if (!arquivo?.pdf || nova < 1 || nova > totalPaginas) return;
    setResultado(null);
    setErro(null);
    setImagemBase64(null);
    await renderizarPagina(arquivo.pdf, nova);
  };

  const enviarOCR = async () => {
    if (!imagemBase64 || !imagemTipo) {
      setErro("Carregue um arquivo primeiro.");
      return;
    }
    setCarregando(true);
    setErro(null);
    setResultado(null);

    const inicio = Date.now();
    try {
      const resp = await fetch(proxyUrl, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ imagem: imagemBase64, tipo: imagemTipo }),
      });

      const data = await resp.json();
      const tempo = ((Date.now() - inicio) / 1000).toFixed(1);

      if (!resp.ok) {
        setErro(data.erro || `Erro HTTP ${resp.status}`);
      } else {
        setResultado(data);
        const esperado = EXPECTED[escola] || null;
        setHistTests(prev => [{
          nome: arquivoNome,
          pagina: paginaAtual,
          tempo,
          layout: data.layout_ocr,
          anos: data.anos_com_notas,
          disciplinas: Object.values(data.anos || {}).reduce((s, a) => Math.max(s, a.quantidade || 0), 0),
          resultado: data,
          esperado,
        }, ...prev.slice(0, 9)]);
      }
    } catch (e) {
      setErro("Erro de conexão: " + e.message + ". Verifique se o servidor PHP está rodando.");
    } finally {
      setCarregando(false);
    }
  };

  const onDrop = useCallback((e) => {
    e.preventDefault();
    const file = e.dataTransfer.files[0];
    if (file) processarArquivo(file);
  }, [processarArquivo]);

  const esperado = EXPECTED[escola] || null;

  return (
    <div style={{fontFamily:"var(--font-sans)", padding:"20px", maxWidth:"1100px", margin:"0 auto"}}>
      <h2 style={{fontSize:"18px", fontWeight:"500", color:"var(--color-text-primary)", marginBottom:"4px"}}>
        Diagnóstico OCR — Histórico Escolar
      </h2>
      <p style={{fontSize:"13px", color:"var(--color-text-secondary)", marginBottom:"20px"}}>
        Teste de precisão do ocr_proxy.php com validação automática contra gabarito
      </p>

      {/* Config URL */}
      <div style={{marginBottom:"16px", display:"flex", alignItems:"center", gap:"10px"}}>
        <label style={{fontSize:"12px", color:"var(--color-text-secondary)", whiteSpace:"nowrap"}}>URL do proxy:</label>
        <input
          value={proxyUrl}
          onChange={e => setProxyUrl(e.target.value)}
          style={{flex:1, padding:"6px 10px", border:"1px solid var(--color-border-primary)", borderRadius:"6px", fontSize:"12px", background:"var(--color-background-secondary)", color:"var(--color-text-primary)"}}
        />
      </div>

      {/* Drop zone */}
      <div
        onDrop={onDrop}
        onDragOver={e => e.preventDefault()}
        onClick={() => document.getElementById("file-inp").click()}
        style={{border:"2px dashed var(--color-border-secondary)", borderRadius:"10px", padding:"28px", textAlign:"center", cursor:"pointer", marginBottom:"16px", background:"var(--color-background-secondary)", transition:"border-color 0.2s"}}
      >
        <div style={{fontSize:"28px", marginBottom:"8px"}}>📄</div>
        <div style={{fontSize:"14px", fontWeight:"500", color:"var(--color-text-primary)"}}>
          {arquivoNome || "Arraste ou clique para carregar"}
        </div>
        <div style={{fontSize:"12px", color:"var(--color-text-tertiary)", marginTop:"4px"}}>
          Suporta PDF, JPG, PNG • PDF renderizado com PDF.js em 2.5× de escala
        </div>
        <input id="file-inp" type="file" accept=".pdf,.jpg,.jpeg,.png" style={{display:"none"}} onChange={e => { if(e.target.files[0]) processarArquivo(e.target.files[0]); }}/>
      </div>

      {/* Preview */}
      {preview && (
        <div style={{marginBottom:"16px"}}>
          <div style={{display:"flex", alignItems:"center", gap:"10px", marginBottom:"8px", flexWrap:"wrap"}}>
            <span style={{fontSize:"12px", color:"var(--color-text-secondary)"}}>
              Página {paginaAtual} de {totalPaginas}
            </span>
            {totalPaginas > 1 && (
              <>
                <button disabled={paginaAtual<=1} onClick={()=>mudarPagina(paginaAtual-1)} style={{padding:"4px 10px",fontSize:"12px",border:"1px solid var(--color-border-primary)",borderRadius:"5px",cursor:"pointer",background:"var(--color-background-secondary)",color:"var(--color-text-primary)"}}>‹ Anterior</button>
                <button disabled={paginaAtual>=totalPaginas} onClick={()=>mudarPagina(paginaAtual+1)} style={{padding:"4px 10px",fontSize:"12px",border:"1px solid var(--color-border-primary)",borderRadius:"5px",cursor:"pointer",background:"var(--color-background-secondary)",color:"var(--color-text-primary)"}}>Próxima ›</button>
              </>
            )}
            <div style={{marginLeft:"auto", display:"flex", gap:"8px", alignItems:"center"}}>
              <span style={{fontSize:"12px", color:"var(--color-text-secondary)"}}>Escola esperada:</span>
              <select value={escola} onChange={e=>setEscola(e.target.value)} style={{padding:"4px 8px",fontSize:"12px",border:"1px solid var(--color-border-primary)",borderRadius:"5px",background:"var(--color-background-secondary)",color:"var(--color-text-primary)"}}>
                <option value="">Sem gabarito</option>
                {Object.keys(EXPECTED).map(e=><option key={e} value={e}>{e}</option>)}
              </select>
            </div>
          </div>
          <img src={preview} alt="Preview" style={{maxWidth:"100%", maxHeight:"400px", border:"1px solid var(--color-border-tertiary)", borderRadius:"8px", objectFit:"contain"}}/>
        </div>
      )}

      {/* Botão enviar */}
      <button
        onClick={enviarOCR}
        disabled={carregando || !imagemBase64}
        style={{padding:"10px 28px", background: carregando||!imagemBase64 ? "var(--color-border-secondary)" : "#1a6ef5", color: carregando||!imagemBase64 ? "var(--color-text-tertiary)":"#fff", border:"none", borderRadius:"8px", fontSize:"14px", fontWeight:"500", cursor: carregando||!imagemBase64 ? "not-allowed":"pointer", marginBottom:"20px"}}
      >
        {carregando ? "⏳ Processando OCR..." : "🔍 Executar OCR"}
      </button>

      {/* Erro */}
      {erro && (
        <div style={{background:"var(--color-background-danger)", color:"var(--color-text-danger)", padding:"12px 16px", borderRadius:"8px", fontSize:"13px", marginBottom:"16px", border:"1px solid var(--color-border-tertiary)"}}>
          ⚠️ {erro}
        </div>
      )}

      {/* Resultado */}
      {resultado && (
        <div style={{background:"var(--color-background-secondary)", borderRadius:"10px", padding:"16px", marginBottom:"24px", border:"1px solid var(--color-border-tertiary)"}}>
          <h3 style={{fontSize:"15px", fontWeight:"500", color:"var(--color-text-primary)", marginBottom:"12px"}}>
            Resultado: {arquivoNome} — Página {paginaAtual}
          </h3>
          <TabelaResultado dados={resultado} esperado={esperado} escola={escola}/>
        </div>
      )}

      {/* Histórico de testes */}
      {histTests.length > 0 && (
        <div>
          <h3 style={{fontSize:"15px", fontWeight:"500", color:"var(--color-text-primary)", marginBottom:"12px"}}>
            Histórico de testes ({histTests.length})
          </h3>
          {histTests.map((t, i) => {
            let corretas = 0, total = 0;
            if (t.esperado) {
              Object.keys(ANOS_LABELS).forEach(a => {
                if (!t.resultado?.anos?.[a]) return;
                Object.keys(DISCIPLINAS_LABELS).forEach(d => {
                  const exp = t.esperado?.[a]?.[d];
                  const got = t.resultado.anos[a]?.disciplinas?.[d];
                  if (exp !== undefined) {
                    total++;
                    if (got !== undefined && Math.abs(got-exp)<0.15) corretas++;
                  }
                });
              });
            }
            const pct = total > 0 ? Math.round(corretas/total*100) : null;
            return (
              <div key={i} style={{background:"var(--color-background-primary)", borderRadius:"8px", padding:"12px 14px", marginBottom:"8px", border:"1px solid var(--color-border-tertiary)", display:"flex", gap:"12px", alignItems:"center", flexWrap:"wrap"}}>
                <span style={{fontSize:"12px", fontWeight:"500", color:"var(--color-text-primary)", minWidth:"140px"}}>{t.nome}</span>
                <span style={{fontSize:"11px", color:"var(--color-text-tertiary)"}}>p.{t.pagina}</span>
                <span style={{fontSize:"11px", color:"var(--color-text-tertiary)"}}>Layout: <b>{t.layout||"?"}</b></span>
                <span style={{fontSize:"11px", color:"var(--color-text-tertiary)"}}>Anos: {t.anos?.join(",")||"—"}º</span>
                <span style={{fontSize:"11px", color:"var(--color-text-tertiary)"}}>Max disc: {t.disciplinas}</span>
                <span style={{fontSize:"11px", color:"var(--color-text-tertiary)"}}>{t.tempo}s</span>
                {pct !== null && (
                  <span style={{fontSize:"11px", fontWeight:"600", color: pct>=90?"var(--color-text-success)": pct>=70?"#856404":"var(--color-text-danger)", background: pct>=90?"var(--color-background-success)": pct>=70?"#fff3cd":"var(--color-background-danger)", padding:"2px 8px", borderRadius:"4px"}}>
                    {pct}% ({corretas}/{total})
                  </span>
                )}
              </div>
            );
          })}
        </div>
      )}

      {/* Legenda dos layouts */}
      <div style={{marginTop:"24px", background:"var(--color-background-tertiary)", borderRadius:"8px", padding:"14px 16px"}}>
        <div style={{fontSize:"12px", fontWeight:"500", color:"var(--color-text-secondary)", marginBottom:"8px"}}>Layouts detectados pelo prompt v13.0 — 6º ao 9º ano</div>
        {Object.entries(LAYOUT_LABELS).map(([k,v]) => (
          <div key={k} style={{fontSize:"12px", color:"var(--color-text-secondary)", marginBottom:"4px"}}>
            <b style={{color:"var(--color-text-primary)"}}>Layout {k}:</b> {v}
          </div>
        ))}
        <div style={{marginTop:"10px", fontSize:"11px", color:"var(--color-text-tertiary)"}}>
          Gabaritos disponíveis: {Object.keys(EXPECTED).join(", ")}
        </div>
      </div>
    </div>
  );
}