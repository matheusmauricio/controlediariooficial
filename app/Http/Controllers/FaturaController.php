<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use App\DiarioData;
use DateTime;
use Illuminate\Support\Collection;
use App\Caderno;
use App\TipoDocumento;
use App\SubCategoria;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use App\Fatura;
use App\Situacao;
use Illuminate\Support\Facades\Session;


class FaturaController extends Controller
{
    //

    private $paginacao = 10;
    public $fileOriginal = "";
    public $fileFormatado = "";

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function carregarConfiguracao(){
        if(Gate::allows('administrador', Auth::user())){
            $confFatura = DB::table('configuracaofatura')->first();
            $cadernos = Caderno::orderBy('cadernoNome')->get();
            return view('fatura.configuracao', ['config' => $confFatura, 'cadernos' => $cadernos]);
        }else{
            return redirect('home');
        }
    }

    public function salvarConfiguracao(Request $request){
        $table = DB::table('configuracaofatura')->orderBy('configID');

        if($request->valorColuna != null && $request->largura != null){
            $table->where('configID', '=', $request->configID)->update(['largura' => $request->largura, 'valorColuna' => $request->valorColuna, 'cadernoID' => $request->cadernoID]);
            return redirect('home')->with('sucesso', 'Configurações Salvas');
        }else{
            return redirect()->back()->with('erro', 'Valores Em Brano');
        }
    }

    public function cadastrar(){

        if(Gate::allows('administrador', Auth::user())){
            $horaEnvio = Auth::user()->horaEnvio;

            $diariosDatas = DiarioData::orderBy('diarioData', 'desc')->where('diarioData', '>', date('Y-m-d'))->get();
            $confFatura = DB::table('configuracaofatura')->first();

            if($confFatura->cadernoID != null){

                $documentos = TipoDocumento::orderBy('tipoDocumento');
                $documentos->join('cadernotipodocumento', 'cadernotipodocumento.tipoID', 'tipodocumento.tipoID');
                $documentos->join('caderno', 'caderno.cadernoID', 'cadernotipodocumento.cadernoID');
                $documentos->where('caderno.cadernoID', '=', $confFatura->cadernoID);
                $documentos = $documentos->get();

                $subcategorias = SubCategoria::orderBy('subcategoriaNome');
                foreach ($documentos as $documento) {
                    $subcategorias->orWhere('tipoID', '=', $documento->tipoID);
                }
                $subcategorias = $subcategorias->get();

            }else{
                return redirect('home')->with('erro', 'Nenhum caderno vinculado com faturas! Vincule nas configurações da fatura.');
            }


            // vericar datas limites para os diários

            $diariosDatasLimites = Collection::make([]);

            foreach($diariosDatas as $diario){

                $diaDiarioDate = new DateTime($diario->diarioData);
                $verificaDiaUtil = false;
                $diaUtil = date('Y-m-d', strtotime("-1 days",strtotime($diaDiarioDate->format('Y-m-d'))));

                do{
                    $finalDeSemana = date('N', strtotime($diaUtil));
                    if(!($finalDeSemana == '7' || $finalDeSemana == '6')){
                        if( !(DB::table('diasnaouteis')->where('diaNaoUtilData', '=', $diaUtil)->count()) ) {
                            $verificaDiaUtil = true;
                            $diariosDatasLimites->push(['diarioData' => $diario->diarioData, 'diarioDataID' => $diario->diarioDataID, 'numeroDiario' => $diario->numeroDiario, 'diaLimite' => $diaUtil]);
                        }else{

                        }
                    }

                    $diaUtil = date('Y-m-d', strtotime("-1 days",strtotime($diaUtil)));
                }while($verificaDiaUtil == false);

            }
            // fim dos limites para os diarios
            return view('fatura.cadastrar', [ 'diarioDatas' => json_encode($diariosDatasLimites), 'horaEnvio' => $horaEnvio, 'config' => $confFatura, 'documentos' => $documentos, 'subcategorias' => $subcategorias]);
        }else{
            return redirect('home');
        }

    }

    public function formatar(Request $request){

        // Toda Vez que formata ele verifica se existem arquivos antigos na pasta temp, se existir os deleta.
        $path = storage_path("app/public/temp/");
        if ($handle = opendir($path)) {
            while (false !== ($file = readdir($handle))) {
                $filelastmodified = filemtime($path . $file);
                //24 hours in a day * 3600 seconds per hour
                if((time() - $filelastmodified) > 1*3600)
                {
                    File::delete($path . $file);
                }
            }
            closedir($handle);
        }
        // fim do delete


        switch ($this->validar($request)){

            case 1:
                return redirect()->back()->with('erro', "Arquivo na extensão incorreta!")->withInput();
            break;

            case 2:
                return redirect()->back()->with('erro', "Arquivo com tamanho maior que 30MB!")->withInput();
            break;

            // case 3:
            //     return redirect()->back()->with('erro', "Nome do requisitante não pode possuir caracteres especiais!")->withInput();
            // break;

            case 4:
                return redirect()->back()->with('erro', "Data de envio ultrapassada!")->withInput();
            break;

            case 5:
                return redirect()->back()->with('erro', "Fatura Já enviada Para o Sistema!")->withInput();
            break;

            case 6:
                return redirect()->back()->with('erro', "Arquivo temporário não existe mais!")->withInput();
            break;

            default:

                // carrega configurações da fatura
                $faturaConfig = DB::table('configuracaofatura')->get();

                $fontSize = 10;      // Muda o Tamnho da Letra       |   MUDAR CASO MUDE ALGUM DIA
                $fontFamily = "Times";  //   A familia da fonte.    |   MUDAR CASO MUDE ALGUM DIA

                // cria o nome dos arquivos temporarios da fatura
                $fileName = $request->cpfCnpj.'-'.date('Y-m-d-H-i-s')."_temp".'.'.pathinfo($request->arquivo->getClientOriginalName(), PATHINFO_EXTENSION);
                $fileNameFormat = $request->cpfCnpj.'-'.date('Y-m-d-H-i-s')."_format"."_temp".'.'.pathinfo($request->arquivo->getClientOriginalName(), PATHINFO_EXTENSION);

                $request->arquivo->storeAs("public/temp/", $fileName);

                $arquivo = \PhpOffice\PhpWord\IOFactory::load(storage_path("app/public/temp/". $fileName));

                // "PhpOffice\PhpWord\Element\TextRun" textrun class
                // "PhpOffice\PhpWord\Element\Text" text class


                // CONFIGURAÇÕES DO TEXTO DO DOCUMENTO

                // ALTERA INFORMAÇÕES PERTINENTE AO TEXTO, TAIS COMO TAMANHO DE LETRA, ALINHAMENTO E TIPO DE FONTE
                $contaParagrafos = 0;
                $passouTitulo = false;
                // dd($arquivo);
                try {
                    foreach($arquivo->getSections()[0]->getElements() as $txtRunOuTxt){

                        // Verifica se é o titulo, 1 pragrafo geralmente é o titulo, e centraliza o titulo
                        // $txtRunOuTxt->paragraphStyle->setLineHeight(1.0);
                        // dd($txtRunOuTxt->paragraphStyle->getLineHeight());


                        if($contaParagrafos == 0){
                            $txtRunOuTxt->fontStyle->bold = true;
                            $txtRunOuTxt->paragraphStyle->alignment = "center";
                        }else{
                            // $txtRunOuTxt->paragraphStyle->spacing = 0;
                            $txtRunOuTxt->paragraphStyle->setSpaceAfter(0);
                            $txtRunOuTxt->paragraphStyle->setSpaceBefore(0);
                            $txtRunOuTxt->paragraphStyle->alignment = "both";
                            // dd($txtRunOuTxt);
                        }


                        if(get_class($txtRunOuTxt) == "PhpOffice\PhpWord\Element\TextRun"){
                            foreach ($txtRunOuTxt->getElements() as $txt) {
                                $txt->fontStyle->name = $fontFamily;
                                $txt->fontStyle->size = $fontSize;

                            }
                        }else{

                            if($txtRunOuTxt->getText() != null){
                                $txtRunOuTxt->fontStyle->name = $fontFamily;
                                $txtRunOuTxt->fontStyle->size = $fontSize;
                            }else{

                                // elimina os espaços em branco dos paragrafos, menos o espaço entre o titulo e o texto.
                                if(!$passouTitulo){
                                    if($contaParagrafos >= 1 && $arquivo->getSections()[0]->getElements()[$contaParagrafos-1]->paragraphStyle->alignment != "center"){
                                        $removeLinha = $arquivo->getSections()[0]->getElements();
                                        unset($removeLinha[$contaParagrafos]);
                                        $arquivo->getSections()[0]->elements = $removeLinha;
                                        $passouTitulo = true;
                                    }
                                }else{
                                    $removeLinha = $arquivo->getSections()[0]->getElements();
                                    unset($removeLinha[$contaParagrafos]);
                                    $arquivo->getSections()[0]->elements = $removeLinha;
                                }
                            }
                        }
                        $contaParagrafos += 1;
                    }

                    //ALTERA AS INFORMAÇÕES PERTINENTE A PAGINA, LARGURA DE MARGEM E TAMANHO DE PAPEL

                $arquivo->getSections()[0]->getStyle()->setMarginBottom("0");
                $arquivo->getSections()[0]->getStyle()->setMarginLeft("0");
                $arquivo->getSections()[0]->getStyle()->setMarginTop("0");
                $arquivo->getSections()[0]->getStyle()->setMarginRight("0");

                $arquivo->getSections()[0]->getStyle()->paper->width = intval(567*$faturaConfig[0]->largura);  //dinamico em twip
                $arquivo->getSections()[0]->getStyle()->setPageSizeW((string)intval(567*$faturaConfig[0]->largura)); //dinamico em twip
                $arquivo->getSections()[0]->getStyle()->paper->sizes["A4"][0] = $faturaConfig[0]->largura*10; //dinamico em mm

                // $arquivo->getSections()[0]->getStyle()->paper->height = null;
                // $arquivo->getSections()[0]->getStyle()->setPageSizeH(null);

                // FIM DAS CONFIGURAÇÕES


                // cria e salva o arquivo formatado no diretorio temporario
                $objectWriter = \PhpOffice\PhpWord\IOFactory::createWriter($arquivo, "Word2007");
                $objectWriter->save(storage_path("app/public/temp/".$fileNameFormat));


                // cria um array com todas as informações da fatura para carrega-la na pagina de visualização
                $filtro = $request->all();
                unset($filtro['arquivo']);

                $arquivosArray = array('arquivoOriginal' => $fileName, 'arquivoFormatado' => $fileNameFormat);

                $filtro += $arquivosArray;
                // fim da criação do array da fatura


                // carrega informações para carregar a pagina de visualização

                $documento = TipoDocumento::orderBy('tipoDocumento');
                $documento->where('tipoID', '=', intval($filtro['tipoID']));
                $documento = $documento->first();

                $subcategoria = SubCategoria::orderBy('subcategoriaNome');
                $subcategoria = $subcategoria->where('subcategoriaID', '=', intval($filtro['subcategoriaID']))->first();

                $diariosData = DiarioData::orderBy('diarioData', 'desc')->where('diarioData', '>', date('Y-m-d'))->where('diarioDataID', '=', $filtro['diarioDataID'])->first();
                $data = new DateTime($diariosData->diarioData);
                $data = $data->format('d/m/Y');

                $infoArray = array('subcategoriaNome' => $subcategoria->subcategoriaNome, 'tipoDocumento' => $documento->tipoDocumento, 'diario' => 'N° '.$diariosData->numeroDiario.'   Data: '.$data);
                $filtro += $infoArray;

                // fim das informações


                // dia limite

                    $diaDiarioDate = new DateTime($diariosData->diarioData);
                    $verificaDiaUtil = false;
                    $diaUtil = date('Y-m-d', strtotime("-1 days",strtotime($diaDiarioDate->format('Y-m-d'))));
                    do{
                        $finalDeSemana = date('N', strtotime($diaUtil));
                        if(!($finalDeSemana == '7' || $finalDeSemana == '6')){
                            if( !(DB::table('diasnaouteis')->where('diaNaoUtilData', '=', $diaUtil)->count()) ) {
                                $verificaDiaUtil = true;
                            }else{
                            }
                        }
                        if($verificaDiaUtil == false){
                            $diaUtil = date('Y-m-d', strtotime("-1 days",strtotime($diaUtil)));
                        }
                    }while($verificaDiaUtil == false);

                // fim do limite

                // necessario criar um json para se entendido pelo javascript
                return view('fatura.formatada', ['formatada' => $arquivo, 'faturaConfig' => $faturaConfig, 'fatura' => $filtro, 'dataLimite' => $diaUtil]);

                } catch (\Exception $e) {

                    return redirect()->back()->with('erro', 'Falha na formatação do arquivo, verifique se o mesmo segue o padrão do template e tente novamente');

                }

            break;
        }
    }


    public function downloadTemp($arquivoFormatadoTemp){
        if(file_exists(storage_path("app/public/temp/".$arquivoFormatadoTemp))){

            return Response::download(storage_path("app/public/temp/".$arquivoFormatadoTemp), 'visualizacao.docx');
        }else{
            return redirect('home')->with('erro', 'Arquivo não Encontrado!');
        }
    }

    public function salvar(Request $request){

        // dd($request);

        switch ($this->validar($request)){

            case 1:
                return redirect('home')->with('erro', "Arquivo na extensão incorreta!");
            break;

            case 2:
                return redirect('home')->with('erro', "Arquivo com tamanho maior que 30MB!");
            break;

            // case 3:
            //     return redirect('home')->with('erro', "Nome do requisitante não pode possuir caracteres especiais!");
            // break;

            case 4:
                return redirect('home')->with('erro', "Data de envio ultrapassada!");
            break;

            case 5:
                return redirect('home')->with('erro', "Fatura Já enviada Para o Sistema!");
            break;

            case 6:
                return redirect('home')->with('erro', "Arquivo temporário não existe mais!");
            break;


            default:

                $this->fileOriginal = str_replace('_temp','',$request->arquivoOriginal);
                $this->fileFormatado = str_replace('_temp','',$request->arquivoFormatado);

                try {

                    $copiaOriginal = File::move(storage_path("app/public/temp/".$request->arquivoOriginal),storage_path("app/".$this->fileOriginal));
                    $copiaFormatado = File::move(storage_path("app/public/temp/".$request->arquivoFormatado),storage_path("app/".$this->fileFormatado));

                    if(DB::table('fatura')->where('protocoloAno', '=', date('Y'))->count() ){
                        $protocolo = DB::table('fatura')->where('protocoloAno', '=', date('Y'))->max('protocolo') + 1;
                    }else{
                        $protocolo = 0;
                    }

                    DB::beginTransaction();
                    $this->verificaProtocolo($protocolo, $request);
                    return redirect('/home')->with('sucesso', 'Fatura Enviada com Sucesso');
                } catch (\Exception $e) {

                    if(file_exists(storage_path("app/".$this->fileOriginal))){
                        Storage::delete([$this->fileOriginal]);
                    }
                    if(file_exists(storage_path("app/".$this->fileFormatado))){
                        Storage::delete([$this->fileFormatado]);
                    }

                    DB::rollBack();

                    return redirect()->back()->with('erro', "Um erro durante a operação ocorreu!".$e->getMessage());
                }

            break;

        }

    }

    public function verificaProtocolo($protocolo, $request){
        if(DB::table('fatura')->where('protocoloAno', '=', date('Y'))->where('protocolo', '=', $protocolo)->count()){
            $protocolo++;
            $this->verificaProtocolo($protocolo, $request);
        }else {
            DB::table('fatura')->insert(['situacaoID' => 4, 'subcategoriaID' => $request->subcategoriaID, 'tipoID' => $request->tipoID, 'diarioDataID' => $request->diarioDataID, 'dataEnvioFatura' => date('Y-m-d H:i:s'), 'arquivoOriginal' => $this->fileOriginal, 'arquivoFormatado' => $this->fileFormatado, 'largura' => $request->largura, 'centimetragem' => $request->centimetragem, 'valorColuna' => $request->valorColuna, 'valor' => $request->valor, 'observacao' => $request->observacao, 'cpfCnpj' => $request->cpfCnpj, 'empresa' => $request->empresa, 'requisitante' => $request->requisitante, 'protocolo' => $protocolo, 'protocoloAno' => date('Y'), 'protocoloCompleto' => $protocolo.date('Y').'FAT', 'usuarioID' => Auth::user()->id]);

            $arquivo = \PhpOffice\PhpWord\IOFactory::load(storage_path("app/".$this->fileFormatado));
            $arquivo->getSections()[0]->addText('Protocolo: '.$protocolo.date('Y').'FAT', array('bold'=>true, 'size'=>10, 'name'=>'Times'));

            $objectWriter = \PhpOffice\PhpWord\IOFactory::createWriter($arquivo, "Word2007");
            $objectWriter->save(storage_path("app/".$this->fileFormatado));

            DB::commit();
        }
    }

    public function validar($request){

        if(isset($request->arquivo)){
            if(pathinfo($request->arquivo->getClientOriginalName(), PATHINFO_EXTENSION) != "docx"){
                return 1;
            }

            $tamanhoArquivo = (filesize($request->arquivo) / 1024)/1024;
            if($tamanhoArquivo >= 30){
                return 2;
            }
        }

        // if (preg_match('', $request->requisitante))
        // {
        //     return 3;
        // }


        // Verificação do lado do servidor sobre a data do envio par o diario !

        $diarioTemp = DiarioData::orderBy('diarioDataID')->where('diarioDataID', '=', $request->diarioDataID)->first();

        $diaDiarioDate = new DateTime($diarioTemp->diarioData);
        $verificaDiaUtil = false;
        $diaUtil = date('Y-m-d', strtotime($diaDiarioDate->format('Y-m-d')));

        do{
            $diaUtil = date('Y-m-d', strtotime("-1 days",strtotime($diaUtil)));
            $finalDeSemana = date('N', strtotime($diaUtil));
            if(!($finalDeSemana == '7' || $finalDeSemana == '6')){
                if( !(DB::table('diasnaouteis')->where('diaNaoUtilData', '=', $diaUtil)->count()) ) {
                    $verificaDiaUtil = true;
                }else{
                }
            }

        }while($verificaDiaUtil == false);

        if($diaUtil <= date('Y-m-d')){
            if($diaUtil == date('Y-m-d')){
                if(Auth::user()->horaEnvio >= date('H:i:s')){

                }else{
                    return 4;
                }
            }else{
                return 4;
            }
        }

        // fim da verificação do lado do servidor


        if(isset($request->arquivoOriginal)){
            $this->fileOriginal = str_replace('_temp','',$request->arquivoOriginal);
            if(file_exists(storage_path("app/".$this->fileOriginal))){
                return 5;
            }

            if(file_exists(storage_path("app/public/temp".$request->arquivoOriginal))){
                return 6;
            }
        }

        return null;

    }

    function validaCNPJ($cnpj = null) {

        // Verifica se um número foi informado
        if(empty($cnpj)) {
            return false;
        }

        // Elimina possivel mascara
        $cnpj = preg_replace("/[^0-9]/", "", $cnpj);
        $cnpj = str_pad($cnpj, 14, '0', STR_PAD_LEFT);

        // Verifica se o numero de digitos informados é igual a 14

        if (strlen($cnpj) != 14) {
            return false;
        }

        // Verifica se nenhuma das sequências invalidas abaixo
        // foi digitada. Caso afirmativo, retorna falso
        else if ($cnpj == '00000000000000' ||
            $cnpj == '11111111111111' ||
            $cnpj == '22222222222222' ||
            $cnpj == '33333333333333' ||
            $cnpj == '44444444444444' ||
            $cnpj == '55555555555555' ||
            $cnpj == '66666666666666' ||
            $cnpj == '77777777777777' ||
            $cnpj == '88888888888888' ||
            $cnpj == '99999999999999') {
            return false;

         // Calcula os digitos verificadores para verificar se o
         // CPF é válido
         } else {

            $j = 5;
            $k = 6;
            $soma1 = "";
            $soma2 = "";

            for ($i = 0; $i < 13; $i++) {

                $j = $j == 1 ? 9 : $j;
                $k = $k == 1 ? 9 : $k;

                $soma2 += ($cnpj{$i} * $k);

                if ($i < 12) {
                    $soma1 += ($cnpj{$i} * $j);
                }

                $k--;
                $j--;

            }

            $digito1 = $soma1 % 11 < 2 ? 0 : 11 - $soma1 % 11;
            $digito2 = $soma2 % 11 < 2 ? 0 : 11 - $soma2 % 11;

            return (($cnpj{12} == $digito1) and ($cnpj{13} == $digito2));

        }
    }

    function validaCPF($cpf = null) {

        // Verifica se um número foi informado
        if(empty($cpf)) {
            return false;
        }

        // Elimina possivel mascara
        $cpf = preg_replace("/[^0-9]/", "", $cpf);
        $cpf = str_pad($cpf, 11, '0', STR_PAD_LEFT);

        // Verifica se o numero de digitos informados é igual a 11
        if (strlen($cpf) != 11) {
            return false;
        }
        // Verifica se nenhuma das sequências invalidas abaixo
        // foi digitada. Caso afirmativo, retorna falso
        else if ($cpf == '00000000000' ||
            $cpf == '11111111111' ||
            $cpf == '22222222222' ||
            $cpf == '33333333333' ||
            $cpf == '44444444444' ||
            $cpf == '55555555555' ||
            $cpf == '66666666666' ||
            $cpf == '77777777777' ||
            $cpf == '88888888888' ||
            $cpf == '99999999999') {
            return false;
         // Calcula os digitos verificadores para verificar se o
         // CPF é válido
         } else {

            for ($t = 9; $t < 11; $t++) {

                for ($d = 0, $c = 0; $c < $t; $c++) {
                    $d += $cpf{$c} * (($t + 1) - $c);
                }
                $d = ((10 * $d) % 11) % 10;
                if ($cpf{$c} != $d) {
                    return false;
                }
            }

            return true;
        }
    }


    public function caixaDeTexto(){

        // Métrica para conversão das medidas na caixa de texto
        // 28,35  = 1 cm;

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection(array('marginTop' => 0,
                                              'marginLeft' => 0,
                                              'marginRight' => 0,
                                              'marginBottom' => 0));
        $textbox = $section->addTextBox(
            array(
                'innerMarginTop' => 0,
                'innerMarginLeft' => 0,
                'innerMarginRight' => 0,
                'innerMarginBottom' => 0,
                'alignment'   => "center",
                'width'       => 439.42, // duas casas após a virgula | largura da caixa de texto
                'height'      => 123.32, // duas casas após a virgula | Comprimento da caixa de texto
                'borderSize'  => 'none',
                'borderColor' => 'white',
            )
        );

        $textbox->addText('meu titulo', array('alignment' => "center"));
        $textbox->addText('texto bolado do jurandir kkkk, jurandir é cara louco, não tem como lidar com ele. Muito bacana mesmo, noosssa tatata!', array('alignment' => "justify"));
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, "Word2007");
        // dd($objWriter);
        $objWriter->save(storage_path('app/'.'dummy.docx'));
    }

    public function listar($cpfCnpj = null, $protocolo = null, $diario = null, $situacao = null, $empresa = null,  $subcategoria = null){

        if(Gate::allows('administrador', Auth::user())){

            $situacoes = Situacao::orderBy('situacaoNome')->get();
            $subcategorias = DB::table('subcategoria')->orderBy('subcategoriaNome')->get();

            $faturas = Fatura::orderBy('dataEnvioFatura', 'desc');
            $faturas->join('diariodata', 'diariodata.diariodataID', 'fatura.diariodataID');

            $faturas->join('subcategoria', 'subcategoria.subcategoriaID', 'fatura.subcategoriaID');
            $faturas->join('situacao', 'situacao.situacaoID', 'fatura.situacaoID');

            // Filtros

                if($empresa != null && $empresa != "tudo"){
                    $arrayPalavras = explode(' ', $empresa);
                    foreach ($arrayPalavras as $palavra) {
                        $faturas->where('empresa', 'like', '%' . $palavra . '%');
                    }
                }

                if($protocolo != null && $protocolo != "tudo"){
                    $faturas->where('protocoloCompleto', '=', $protocolo);
                }

                if($cpfCnpj != null && $cpfCnpj != "tudo"){
                    $faturas->where('cpfCnpj', '=', $cpfCnpj);
                }

                if($diario != null && $diario != "tudo"){
                    $faturas->where('diariodata.diarioData', '=', $diario);
                }

                if($situacao != null && $situacao != "tudo"){
                    $faturas->where('situacao.situacaoNome', '=', $situacao);
                }

                if($subcategoria != null && $subcategoria != "tudo"){
                        $faturas->where('fatura.subcategoriaID', '=', $subcategoria);
                }

            // Fim Filtros

            $faturas->select('fatura.*', 'diariodata.numeroDiario', 'diariodata.diarioData', 'situacao.situacaoNome', 'subcategoria.subcategoriaNome');
            $faturas = $faturas->paginate($this->paginacao);
            return view('fatura.listar', ['faturas' => $faturas, 'subcategorias' => $subcategorias, 'situacoes' => $situacoes]);

        }else{

            return redirect('home');

        }
    }


    public function listarFiltro(Request $request){

        if($request->cpfCnpj != null){
            $cpfCnpj = $request->cpfCnpj;
        }else{
            $cpfCnpj = "tudo";
        }

        if($request->protocolo != null){
            $protocolo = $request->protocolo;
        }else{
            $protocolo = "tudo";
        }

        if($request->diario != null){
            $diario = $request->diario;
        }else{
            $diario = "tudo";
        }

        if($request->situacao == "tudo" ){
            $situacao = "tudo";
        }else{
            $situacao = $request->situacao;
        }

        if($request->empresa != null){
            $empresa = $request->empresa;
        }else{
            $empresa = "tudo";
        }

        if($request->subcategoria != null){
            $subcategoria = $request->subcategoria;
        }else{
            $subcategoria = "tudo";
        }

        if(($diario == "tudo") && ($cpfCnpj == "tudo") && ($protocolo == "tudo") && ($situacao == "tudo") && ($empresa == "tudo") && ($subcategoria == "tudo")){
            return redirect('fatura/listar');
        }else{
            return redirect()->route('listarFaturas', ['cpfCnpj' => $cpfCnpj, 'protocolo' => $protocolo, 'diario' => $diario, 'situacao' => $situacao, 'empresa' => $empresa, 'subcategoria' => $subcategoria]);
        }

    }

    public function ver($protocolo){


        if(Gate::allows('administrador', Auth::user())){

            $fatura = Fatura::orderBy('diariodata.diarioData', 'desc');
            $fatura->join('diariodata', 'diariodata.diariodataID', 'fatura.diariodataID');
            $fatura->join('users as usuario', 'usuario.id', 'fatura.usuarioID');
            $fatura->join('subcategoria', 'subcategoria.subcategoriaID', 'fatura.subcategoriaID');
            $fatura->join('tipodocumento', 'tipodocumento.tipoID', 'subcategoria.tipoID');
            $fatura->join('situacao', 'situacao.situacaoID', 'fatura.situacaoID');
            $fatura->where('protocoloCompleto', '=', $protocolo);
            $fatura->select('fatura.*', 'diariodata.numeroDiario', 'diariodata.diarioData', 'situacao.situacaoNome', 'subcategoria.subcategoriaNome', 'tipodocumento.tipoDocumento', 'usuario.name as usuarioNome');
            $fatura = $fatura->first();

            // pega a url voltar e salva
            if(url()->previous() != url()->current()){
                Session::put('urlVoltar', url()->previous());
            }

            $faturaConfig = DB::table('configuracaofatura')->get();

            if($fatura->situacaoNome != "Apagada"){
                $formatada =  \PhpOffice\PhpWord\IOFactory::load(storage_path("app/". $fatura->arquivoFormatado));
                return view('fatura.ver', ['fatura' => $fatura, 'formatada' => $formatada, 'faturaConfig' => $faturaConfig]);
            }else{
                return view('fatura.ver', ['fatura' => $fatura, 'faturaConfig' => $faturaConfig]);
            }

        }else{
            return redirect('home');

        }

    }

    public function Rejeitar(Request $request){

        $protocolo = $request->protocolo;
        $fatura = Fatura::orderBy('protocoloAno', 'desc');

        if($protocolo != null){
            if(strlen($protocolo) > 7){
                $fatura->where('protocoloCompleto', '=', $protocolo);
            }else{
                return redirect()->back()->with(['erro' => 'Fatura não encontrada!']);
            }
        }else{
            return redirect()->back()->with(['erro' => 'Fatura não encontrada!']);
        }

        if(strlen($request->descricao) >= 255){
            return redirect()->back()->with(['erro' => 'Tamanho da descrição excedida!']);
        }

        try {
            $fatura->update(['situacaoID' => 5, 'descricaoCancelamento' => $request->descricao]);
            return redirect()->to(Session::get('urlVoltar'))->with('sucesso', 'Fatura Rejeitada!');
        } catch (\Throwable $th) {
            return redirect()->back()->with(['erro' => 'Fatura não encontrada!']);
        }

    }

    public function aceitar(Request $request){
        $protocolo = $request->protocolo;
        $fatura = Fatura::orderBy('protocoloAno', 'desc');

        if($protocolo != null){
            if(strlen($protocolo) > 7){
                $fatura->where('protocoloCompleto', '=', $protocolo);
            }else{
                return redirect()->back()->with(['erro' => 'Fatura não encontrada!']);
            }
        }else{
            return redirect()->back()->with(['erro' => 'Fatura não encontrada!']);
        }

        if(pathinfo($request->arquivo->getClientOriginalName(), PATHINFO_EXTENSION) != "pdf"){
            return redirect()->back()->with(['erro' => 'Arquivo com extensão errada, somente PDF!']);
        }

        $tamanhoArquivo = (filesize($request->arquivo) / 1024)/1024;
        if($tamanhoArquivo >= 30){
            return redirect()->back()->with(['erro' => 'Arquivo com tamanho excedido!']);
        }

        try {

            $request->arquivo->storeAs("",$request->protocolo."_comprovantePago.pdf");
            $fatura->update(['situacaoID' => 3, 'comprovantePago' => $request->protocolo."_comprovantePago.pdf"]);

            return redirect()->to(Session::get('urlVoltar'))->with('sucesso', 'Fatura Aceita!');

        } catch (\Exception $e) {

            return redirect()->back()->with(['erro' => 'Ocorreu um erro! erro: '.$e->getMessage()]);

        }

    }

    public function publicar(Request $request){

        $protocolo = $request->protocolo;
        $fatura = Fatura::orderBy('protocoloAno', 'desc');

        if($protocolo != null){
            if(strlen($protocolo) > 7){
                $fatura->where('protocoloCompleto', '=', $protocolo);
            }else{
                return redirect()->back()->with('erro', 'Fatura não encontrada!');
            }
        }else{
            return redirect()->back()->with('erro', 'Fatura não encontrada!');
        }

        try {

            $fatura->update(['situacaoID' => 1]);
            return redirect()->back()->with('sucesso', 'Fatura Publicada!');

        } catch (\Exception $e) {

            return redirect()->back()->with(['erro' => 'Ocorreu um erro! erro: ']);

        }

    }


    public function downloadOriginal($protocolo){

        if(Gate::allows('administrador', Auth::user())){

            $fatura = Fatura::orderBy('protocoloAno', 'desc');
            $fatura->join('situacao', 'situacao.situacaoID', 'fatura.situacaoID');

            $fatura->where('protocoloCompleto', '=', $protocolo);

            $fatura = $fatura->first();

            if($fatura == null){
                return redirect()->back()->with('erro', 'Protocolo não encontrado!');
            }

            if($fatura->situacaoNome == "Apagada"){
                return redirect()->back()->with('erro', 'Arquivo não encontrado!');
            }

            if ($fatura != null) {
                if(!Gate::allows('administrador', Auth::user()) && Auth::user()->id != $fatura->usuarioID){
                    return redirect()->back()->with('erro', 'Você não tem permissão!');
                }
            }else{
                return redirect()->back()->with('erro', 'Arquivo não encontrado!');
            }

            $arquivoExtensao = explode('.', $fatura->arquivoOriginal);

            if(file_exists(storage_path("app/".$fatura->arquivoOriginal))){
                return Response::download(storage_path("app/".$fatura->arquivoOriginal), ''.$protocolo.'-'.'Original'.'.'.$arquivoExtensao[1]);
            }else{
                return redirect()->back()->with('erro', 'Arquivo não Encontrado!');
            }

        }else{
            return redirect('home');
        }
    }

    public function downloadFormatado($protocolo){

        if(Gate::allows('administrador', Auth::user())){

            $fatura = Fatura::orderBy('protocoloAno', 'desc');
            $fatura->join('situacao', 'situacao.situacaoID', 'fatura.situacaoID');

            $fatura->where('protocoloCompleto', '=', $protocolo);

            $fatura = $fatura->first();

            if($fatura == null){
                return redirect()->back()->with('erro', 'Protocolo não encontrado!');
            }

            if($fatura->situacaoNome == "Apagada"){
                return redirect()->back()->with('erro', 'Arquivo não encontrado!');
            }

            if ($fatura != null) {
                if(!Gate::allows('administrador', Auth::user()) && Auth::user()->id != $fatura->usuarioID){
                    return redirect()->back()->with('erro', 'Você não tem permissão!');
                }
            }else{
                return redirect()->back()->with('erro', 'Arquivo não encontrado!');
            }

            $arquivoExtensao = explode('.', $fatura->arquivoFormatado);

            if(file_exists(storage_path("app/".$fatura->arquivoFormatado))){
                return Response::download(storage_path("app/".$fatura->arquivoFormatado), ''.$protocolo.'-'.'Formatado'.'.'.$arquivoExtensao[1]);
            }else{
                return redirect()->back()->with('erro', 'Arquivo não Encontrado!');
            }

        }else{
            return redirect('home');
        }
    }

    public function downloadComprovantePago($protocolo){

        if(Gate::allows('administrador', Auth::user())){

            $fatura = Fatura::orderBy('protocoloAno', 'desc');
            $fatura->join('situacao', 'situacao.situacaoID', 'fatura.situacaoID');

            $fatura->where('protocoloCompleto', '=', $protocolo);

            $fatura = $fatura->first();

            if($fatura == null){
                return redirect()->back()->with('erro', 'Protocolo não encontrado!');
            }

            if($fatura->situacaoNome == "Apagada"){
                return redirect()->back()->with('erro', 'Arquivo não encontrado!');
            }

            if ($fatura != null) {
                if(!Gate::allows('administrador', Auth::user()) && Auth::user()->id != $fatura->usuarioID){
                    return redirect()->back()->with('erro', 'Você não tem permissão!');
                }
            }else{
                return redirect()->back()->with('erro', 'Arquivo não encontrado!');
            }

            $arquivoExtensao = explode('.', $fatura->comprovantePago);
            if(file_exists(storage_path("app/".$fatura->comprovantePago))){
                return Response::download(storage_path("app/".$fatura->comprovantePago), ''.$protocolo.'-'.'ComprovantePago'.'.'.$arquivoExtensao[1]);
            }else{
                return redirect()->back()->with('erro', 'Arquivo não Encontrado!');
            }

        }else{
            return redirect('home');
        }
    }

    public function apagar(Request $request){

        $protocolo = $request->protocolo;

        $fatura = Fatura::orderBy('protocoloAno', 'desc');
        $fatura->where('protocoloCompleto', '=', $protocolo);
        $fatura = $fatura->first();

        $faturaApagar = Fatura::orderBy('protocoloAno', 'desc');

        if($protocolo != null){
            if(strlen($protocolo) > 7 && $fatura != null){
                $faturaApagar->where('protocoloCompleto', '=', $protocolo);
            }else{
                return redirect()->back()->with('erro', 'Fatura não encontrada!');
            }
        }else{
            return redirect()->back()->with('erro', 'Fatura não encontrada!');
        }


        try {

            if(file_exists(storage_path("app/".$fatura->arquivoOriginal))){
                Storage::delete([$fatura->arquivoOriginal]);
            }
            if(file_exists(storage_path("app/".$fatura->arquivoFormatado))){
                Storage::delete([$fatura->arquivoFormatado]);
            }
            if(file_exists(storage_path("app/".$fatura->comprovantePago))){
                Storage::delete([$fatura->comprovantePago]);
            }

            $faturaApagar->update(['situacaoID' => 2]);
            return redirect()->back()->with('sucesso', 'Fatura Apagada!');

        } catch (\Exception $e) {
            return redirect()->back()->with('erro', 'Ocorreu um erro durante o processo! Erro: '.$e->getMessage());
        }

        // verifica se existe o arquivo e o deleta;
    }

    public function carregarRelatorio($dataInicio = null, $dataFinal = null, $situacao = null){
        if(Gate::allows('administrador', Auth::user())){

            $situacoes = Situacao::orderBy('situacaoNome')->get();

            $faturas = Fatura::orderBy('protocolo');
            $faturas->join('situacao', 'situacao.situacaoID', 'fatura.situacaoID');
            $faturas->whereBetween('dataEnvioFatura',  [$dataInicio . ' 00:00:01', $dataFinal . ' 23:59:59']);

            $subcategorias = SubCategoria::orderBy('subcategoriaNome');
            $subcategorias->join('fatura', 'fatura.subcategoriaID', 'subcategoria.subcategoriaID');
            $subcategorias->join('situacao', 'situacao.situacaoID', 'fatura.situacaoID');
            $subcategorias->selectRaw('SUM(fatura.valor) as total');
            $subcategorias->selectRaw('COUNT(*) as quantidade, subcategoria.subcategoriaNome');
            $subcategorias->whereBetween('fatura.dataEnvioFatura',  [$dataInicio . ' 00:00:01', $dataFinal . ' 23:59:59']);
            $subcategorias->groupBy('subcategoria.subcategoriaNome');

            $valorTotal = DB::table('fatura');
            $valorTotal->selectRaw('SUM(valor) as total');
            $valorTotal->join('situacao', 'situacao.situacaoID', 'fatura.situacaoID');
            $valorTotal->whereBetween('dataEnvioFatura',  [$dataInicio . ' 00:00:01', $dataFinal . ' 23:59:59']);

            if($situacao != null && $situacao != "tudo"){

                if(preg_match('/-/', $situacao)){
                    $situacao = explode('-', $situacao);
                    $i = 0;

                    foreach($situacao as $sit){
                        if($i == 0){
                            $faturas->where('situacao.situacaoNome', '=', $sit);
                            $subcategorias->where('situacao.situacaoNome', '=', $sit);
                            $valorTotal->where('situacao.situacaoNome', '=', $sit);
                        }else{
                            $faturas->orWhere('situacao.situacaoNome', '=', $sit);
                            $subcategorias->orWhere('situacao.situacaoNome', '=', $sit);
                            $valorTotal->orwhere('situacao.situacaoNome', '=', $sit);
                        }
                        $i++;
                    }

                }else{
                    $faturas->where('situacao.situacaoNome', '=', $situacao);
                    $subcategorias->where('situacao.situacaoNome', '=', $situacao);
                    $valorTotal->where('situacao.situacaoNome', '=', $situacao);
                }

            }

            $faturas = $faturas->count();
            $subcategorias = $subcategorias->get();
            $valorTotal = $valorTotal->first();

            if($dataInicio != null && $dataFinal != null && $situacao != null){
                return view('fatura.relatorio',  ['faturas' => $faturas, 'subcategorias' => $subcategorias, 'valorTotal' => $valorTotal, 'situacoes' => $situacoes]);
            }else{
                return view('fatura.relatorio', ['situacoes' => $situacoes]);
            }
        }else{
            return redirect('home');
        }
    }

    public function carregarRelatorioFiltro(Request $request){

        if($request->situacao != null && $request->situacao != "tudo" ){
            $situacao = $request->situacao;
        }else{
            $situacao = "tudo";
        }

        return redirect()->route('carregarRelatorio', ['dataInicio' => $request->dataInicio, 'dataFinal' => $request->dataFinal, 'situacao' => $situacao]);
    }

}
