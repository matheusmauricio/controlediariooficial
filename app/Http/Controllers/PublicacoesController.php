<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Publicacao;
use Illuminate\Support\Facades\DB;
use App\Caderno;
use App\TipoDocumento;
use App\CadernoTipoDocumento;
use Illuminate\Support\Facades\Auth;
use App\DiarioData;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use App\OrgaoRequisitante;
use Illuminate\Support\Facades\Session;
use DateTime;
use Illuminate\Support\Collection;



class PublicacoesController extends Controller
{
    //
    private $paginacao = 10;
    public $fileName = "";

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function listarFiltroApagadas(Request $request){

        if($request->nomeUsuario != null){
            $nome = $request->nomeUsuario;
        }else{
            $nome = "tudo";
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

        if($request->orgao != null){
            $orgao = $request->orgao;
        }else{
            $orgao = "tudo";
        }

        if(($diario == "tudo") && ($nome == "tudo") && ($protocolo == "tudo") && ($orgao == "tudo")){
            return redirect('publicacao/apagadas');
        }else{
            return redirect()->route('listarApagadas', ['nome' => $nome, 'protocolo' => $protocolo, 'diario' => $diario, 'orgao' => $orgao]);
        }

    }

    public function apagadas($nome = null, $protocolo = null, $diario = null, $orgao = null){

        $publicacoes = Publicacao::orderBy('protocoloAno', 'desc')->orderBy('protocolo', 'desc');
        $orgaos = OrgaoRequisitante::orderBy('orgaoNome')->get();

        $publicacoes->join('users as criado', 'criado.id', 'publicacao.usuarioID');
        $publicacoes->join('users as apagado', 'apagado.id', 'publicacao.usuarioIDApagou');
        $publicacoes->join('diariodata', 'diariodata.diarioDataID', 'publicacao.diarioDataID');
        $publicacoes->join('situacao', 'situacao.situacaoID', 'publicacao.situacaoID');
        $publicacoes->join('orgaorequisitante', 'orgaorequisitante.orgaoID', 'criado.orgaoID');

        if($nome != null && $nome != "tudo"){
            $arrayPalavras = explode(' ', $nome);
            foreach ($arrayPalavras as $palavra) {
                $publicacoes->where('criado.name', 'like', '%' . $palavra . '%');
            }
        }

        if($protocolo != null && $protocolo != "tudo"){
            if(strlen($protocolo) > 7){
                $publicacoes->where('protocoloCompleto', '=', $protocolo);
            }else{
                $publicacoes->where('protocolo', '=', null);
            }

        }

        if($diario != null && $diario != "tudo"){
            $publicacoes->where('diariodata.diarioData', '=', $diario);
        }

        if($orgao != null && $orgao != "tudo"){
            $publicacoes->where('orgaorequisitante.orgaoID', '=', $orgao);
        }

        $publicacoes->where('situacao.situacaoNome', '=', "Apagada");

        if(!Gate::allows('administrador', Auth::user())){
            $publicacoes->where('usuarioID', '=', Auth::user()->id);

        }

        $publicacoes->select('publicacao.*', 'situacao.situacaoNome', 'diariodata.diarioData', 'diariodata.numeroDiario', 'criado.name as nomeUsuarioCriou', 'apagado.name as nomeUsuarioApagou', 'orgaorequisitante.orgaoNome');
        $publicacoes = $publicacoes->paginate($this->paginacao);

        return view('publicacao.apagadas', ['publicacoes' => $publicacoes, 'orgaos' => $orgaos]);
    }


    public function listar($nome = null, $protocolo = null, $diario = null, $situacao = null, $orgao = null){

        $situacoes = DB::table('situacao')->get();
        $orgaos = OrgaoRequisitante::orderBy('orgaoNome')->get();


        $publicacoes = Publicacao::orderBy('protocoloAno', 'desc')->orderBy('protocolo', 'desc');

        $publicacoes->join('users', 'users.id', 'publicacao.usuarioID');
        $publicacoes->join('diariodata', 'diariodata.diarioDataID', 'publicacao.diarioDataID');
        $publicacoes->join('situacao', 'situacao.situacaoID', 'publicacao.situacaoID');
        $publicacoes->join('orgaorequisitante', 'orgaorequisitante.orgaoID', 'users.orgaoID');

        if($nome != null && $nome != "tudo"){
            $arrayPalavras = explode(' ', $nome);
            foreach ($arrayPalavras as $palavra) {
                $publicacoes->where('users.name', 'like', '%' . $palavra . '%');
            }
        }

        if($protocolo != null && $protocolo != "tudo"){
            if(strlen($protocolo) > 7){
                $publicacoes->where('protocoloCompleto', '=', $protocolo);
            }else{
                $publicacoes->where('protocolo', '=', null);
            }
        }

        if($diario != null && $diario != "tudo"){
            $publicacoes->where('diariodata.diarioData', '=', $diario);
        }

        if($situacao != null && $situacao != "tudo"){
            $publicacoes->where('situacao.situacaoNome', '=', $situacao);
        }

        if($orgao != null && $orgao != "tudo"){
                $publicacoes->where('orgaorequisitante.orgaoID', '=', $orgao);
        }



        if(!Gate::allows('administrador', Auth::user())){
            $publicacoes->where('usuarioID', '=', Auth::user()->id);
        }

        $publicacoes->select('publicacao.*', 'situacao.situacaoNome', 'diariodata.diarioData', 'diariodata.numeroDiario', 'users.name as nomeUsuario', 'orgaorequisitante.orgaoNome');
        $publicacoes = $publicacoes->paginate($this->paginacao);

        return view('publicacao.listar', ['publicacoes' => $publicacoes, 'situacoes' => $situacoes, 'orgaos' => $orgaos]);
    }


    public function listarFiltro(Request $request){

        if($request->nomeUsuario != null){
            $nome = $request->nomeUsuario;
        }else{
            $nome = "tudo";
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

        if($request->orgao != null){
            $orgao = $request->orgao;
        }else{
            $orgao = "tudo";
        }


        if(($diario == "tudo") && ($nome == "tudo") && ($protocolo == "tudo") && ($situacao == "tudo") && ($orgao == "tudo")){
            return redirect('publicacao/listar');
        }else{
            return redirect()->route('listarPublicacoes', ['nome' => $nome, 'protocolo' => $protocolo, 'diario' => $diario, 'situacao' => $situacao, 'orgao' => $orgao]);
        }

    }


    public function cadastrar(){

        $usuarioCaderno = DB::table('usuariocaderno')->join('caderno', 'caderno.cadernoID', 'usuariocaderno.cadernoID')->where('usuarioID', '=', Auth::user()->id)->select('caderno.*')->get();
        $horaEnvio = Auth::user()->horaEnvio;
        $documentos = TipoDocumento::orderBy('tipoDocumento');
        $documentos->join('cadernotipodocumento', 'tipodocumento.tipoID',  '=', 'cadernotipodocumento.tipoID');
        foreach($usuarioCaderno as $caderno){
            $documentos->orWhere('cadernotipodocumento.cadernoID', '=', $caderno->cadernoID);
        }
        $documentos->select('cadernotipodocumento.cadernoID', 'tipodocumento.tipoID', 'tipodocumento.tipoDocumento');
        $documentos = $documentos->get();

        $diariosDatas = DiarioData::orderBy('diarioData', 'desc')->where('diarioData', '>', date('Y-m-d'))->get();


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
        return view('publicacao.cadastrar', ['usuarioCaderno' => $usuarioCaderno, 'documentos' => $documentos, 'diarioDatas' => json_encode($diariosDatasLimites), 'horaEnvio' => $horaEnvio]);
    }


    public function salvar(Request $request){

        switch ($this->validar($request)){

            case 1:
                return redirect()->back()->with('erro', "Arquivo não deve exceder o tamanho de 30 MB!")->withInput();
            break;

            case 2:
                return redirect()->back()->with('erro', "Arquivo na extensão incorreta!")->withInput();
            break;

            case 3:
                return redirect()->back()->with('erro', "Tamanho da descrição excedida!")->withInput();
            break;

            case 4:
                return redirect()->back()->with('erro', "Tamanho do título excedido!")->withInput();
            break;

            case 5:
                return redirect()->back()->with('erro', "Data de envio ultrapassada!")->withInput();
            break;

            default:

                if(isset($request->protocolo)){

                    if($request->protocolo!= null){
                        if(strlen($request->protocolo) > 7){
                            $protocoloCompleto = $request->protocolo;
                        }else{
                            return redirect()->back()->with('erro', "Protocolo invalido!")->withInput();
                        }
                    }else{
                        return redirect()->back()->with('erro', "Protocolo invalido!")->withInput();
                    }


                    if(Session::get('protocoloEditar') != $request->protocolo){
                        return redirect()->back()->with('erro', "Protocolo invalido (Tentativa de Violar o Sistema) !!")->withInput();
                    }




                    try {
                        DB::beginTransaction();
                        if(!isset($request->manterArquivo)){
                            $this->fileName =  Auth::user()->id.date('Y-m-d-H-i-s').".".pathinfo($request->arquivo->getClientOriginalName(), PATHINFO_EXTENSION);
                            $request->arquivo->storeAs("", $this->fileName);
                            DB::table('publicacao')->where('protocoloCompleto', '=', $protocoloCompleto)->update(['cadernoID' => $request->cadernoID, 'tipoID' => $request->tipoID, 'usuarioID' => Auth::user()->id, 'diarioDataID' => $request->diarioDataID, 'dataEnvio' => date('Y-m-d H:i:s'), 'arquivo' => $this->fileName, 'titulo' => $request->titulo, 'descricao' => $request->descricao]);
                        }else{
                            DB::table('publicacao')->where('protocolo', '=', $protocoloCompleto)->update(['cadernoID' => $request->cadernoID, 'tipoID' => $request->tipoID, 'usuarioID' => Auth::user()->id, 'diarioDataID' => $request->diarioDataID, 'dataEnvio' => date('Y-m-d H:i:s'), 'titulo' => $request->titulo, 'descricao' => $request->descricao]);
                        }
                        DB::commit();
                        Session::forget('protocoloEditar');

                        return redirect('/home')->with('sucesso', 'Publicação Editada com Sucesso');

                    } catch (\Exception $e) {

                        if(file_exists(storage_path("app/".$this->fileName))){
                            Storage::delete([$this->fileName]);
                        }
                        DB::rollBack();
                        return redirect()->back()->with('erro', "Um erro durante a operação ocorreu!".$e->getMessage())->withInput();
                    }


                }else{

                    $this->fileName =  Auth::user()->id.date('Y-m-d-H-i-s').".".pathinfo($request->arquivo->getClientOriginalName(), PATHINFO_EXTENSION);

                    try {
                        $request->arquivo->storeAs("", $this->fileName);
                        if(DB::table('publicacao')->where('protocoloAno', '=', date('Y'))->count() ){
                            $protocolo = DB::table('publicacao')->where('protocoloAno', '=', date('Y'))->max('protocolo') + 1;
                        }else{
                            $protocolo = 0;
                        }

                        DB::beginTransaction();
                        $this->verificaProtocolo($protocolo, $request);

                        return redirect('/home')->with('sucesso', 'Publicação Enviada com Sucesso');
                    } catch (\Exception $e) {

                        if(file_exists(storage_path("app/".$this->fileName))){
                            Storage::delete([$this->fileName]);
                        }
                        DB::rollBack();
                        return redirect()->back()->with('erro', "Um erro durante a operação ocorreu!".$e->getMessage())->withInput();
                    }

                }

            break;

        }

    }

    public function verificaProtocolo($protocolo, $request){
        if(DB::table('publicacao')->where('protocoloAno', '=', date('Y'))->where('protocolo', '=', $protocolo)->count()){
            $protocolo++;
            $this->verificaProtocolo($protocolo, $request);
        }else {
            DB::table('publicacao')->insert(['situacaoID' => 4, 'cadernoID' => $request->cadernoID, 'tipoID' => $request->tipoID, 'usuarioID' => Auth::user()->id, 'diarioDataID' => $request->diarioDataID, 'dataEnvio' => date('Y-m-d H:i:s'), 'arquivo' => $this->fileName, 'titulo' => $request->titulo, 'descricao' => $request->descricao, 'protocolo' => $protocolo, 'protocoloAno' => date('Y'), 'protocoloCompleto' => $protocolo.date('Y').'PUB']);
            DB::commit();
        }
    }

    public function validar($request){

        $diarioTemp = DiarioData::orderBy('diarioDataID')->where('diarioDataID', '=', $request->diarioDataID)->first();

        if(!isset($request->manterArquivo)){
            $tamanhoArquivo = ((filesize($request->arquivo) / 1024)/1024);
            if($tamanhoArquivo >= 30){
                return 1;
            }

            $extensões = array('pdf', 'docx', 'odt', 'rtf', 'doc', 'xlsx', 'xls');
            $extensao = pathinfo($request->arquivo->getClientOriginalName(), PATHINFO_EXTENSION);

            if(!in_array($extensao, $extensões)){
                return 2;
            }
        }

        if(strlen($request->descricao) > 255){
            return 3;
        }

        if(strlen($request->titulo) > 100){
            return 4;
        }

        // Verificação do lado do servidor sobre a data do envio par o diario !

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
                    return 5;
                }
            }else{
                return 5;
            }
        }

        // fim da verificação do lado do servidor
    }



    public function editar($protocolo){

        if($protocolo != null){
            if(strlen($protocolo) > 7){
                $protocoloCompleto = $protocolo;
            }else{
                $protocoloCompleto = null;
            }
        }



        // Verifica se essa publicação foi apagada

        $usuarioIDApagou = Publicacao::orderBy('protocoloAno', 'desc');
        $usuarioIDApagou->where('protocoloCompleto', '=', $protocoloCompleto);
        $usuarioIDApagou = $usuarioIDApagou->first();


        //verifica se a publicação é nula!
        //se não, verifica se o usuario é comum e esta tentando entrar com protocolo de uma publicação que não é dele

        if($usuarioIDApagou != null){
            if(!Gate::allows('administrador', Auth::user()) && Auth::user()->id != $usuarioIDApagou->usuarioID){
                return redirect('/home')->with('erro', 'Você não tem permissão!');
              }
            // Busca todos os dados da visualização

            $publicacao = Publicacao::orderBy('protocoloAno', 'desc')->orderBy('protocolo', 'desc');

            if($usuarioIDApagou->usuarioIDApagou != null){
                $publicacao->join('users as apagado', 'apagado.id', 'publicacao.usuarioIDApagou');
            }

            $publicacao->join('users as criado', 'criado.id', 'publicacao.usuarioID');
            $publicacao->join('diariodata', 'diariodata.diarioDataID', 'publicacao.diarioDataID');
            $publicacao->join('situacao', 'situacao.situacaoID', 'publicacao.situacaoID');
            $publicacao->join('caderno', 'caderno.cadernoID', 'publicacao.cadernoID');
            $publicacao->join('tipodocumento', 'tipodocumento.tipoID', 'publicacao.tipoID');
            $publicacao->where('protocoloCompleto', '=', $protocoloCompleto);
            if($usuarioIDApagou->usuarioIDApagou != null){
                $publicacao->select('publicacao.*', 'caderno.cadernoNome', 'tipodocumento.tipoDocumento', 'diariodata.*', 'situacao.*', 'criado.name as nomeUsuarioCriado', 'apagado.name as nomeUsuarioApagado');
            }else{
                $publicacao->select('publicacao.*', 'caderno.cadernoNome', 'tipodocumento.tipoDocumento', 'diariodata.*', 'situacao.*', 'criado.name as nomeUsuarioCriado');
            }
            $publicacao = $publicacao->first();

        }else{
            return redirect('/home')->with('erro', 'Não existe publicação com esse protocolo!');
        }


        // verifica pode editar

        if($usuarioIDApagou->usuarioIDApagou != null){
            $podeEditar = false;
        }else{
            if(Gate::allows('administrador', Auth::user()) && date('Y-m-d') < $publicacao->diarioData){
                $podeEditar = true;
            }else{
                if ($publicacao->situacaoNome == "Publicada" || $publicacao->situacaoNome == "Aceita" || date('Y-m-d') >= $publicacao->diarioData){
                    $podeEditar = false;
                }else{
                    $podeEditar = true;
                }
            }
        }

        // Se pode editar então carrega os dados para edição e retorna view

        if($podeEditar){
            Session::put('protocoloEditar', $protocolo);
            $situacoes = DB::table('situacao')->get();
            $usuarioCaderno = DB::table('usuariocaderno')->join('caderno', 'caderno.cadernoID', 'usuariocaderno.cadernoID')->where('usuarioID', '=', Auth::user()->id)->select('caderno.*')->get();

            $documentos = TipoDocumento::orderBy('tipoDocumento');
            $documentos->join('cadernotipodocumento', 'tipodocumento.tipoID',  '=', 'cadernotipodocumento.tipoID');
            foreach($usuarioCaderno as $caderno){
                $documentos->orWhere('cadernotipodocumento.cadernoID', '=', $caderno->cadernoID);
            }
            $documentos->select('cadernotipodocumento.cadernoID', 'tipodocumento.tipoID', 'tipodocumento.tipoDocumento');
            $documentos = $documentos->get();

            $diariosDatas = DiarioData::orderBy('diarioData', 'desc')->where('diarioData', '>', date('Y-m-d'))->get();
            $horaEnvio = Auth::user()->horaEnvio;
            // Inicio da verificação dos dias limites

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

            return view('publicacao.editar', ['publicacao' => $publicacao, 'podeEditar' => $podeEditar, 'diarioDatas' => $diariosDatas, 'documentos' => $documentos, 'usuarioCaderno' => $usuarioCaderno, 'situacao' => $situacoes, 'diarioDatas' => json_encode($diariosDatasLimites), 'horaEnvio' => $horaEnvio]);
        }else{
            return redirect('home')->with('erro', 'Você não pode realizar essa ação!');
        }
    }


    public function ver($protocolo){
        if($protocolo != null){
            if(strlen($protocolo) > 7){
                $protocoloCompleto = $protocolo;
            }else{
                $protocoloCompleto = null;
            }
        }

        // Verifica se essa publicação foi apagada

        $usuarioIDApagou = Publicacao::orderBy('protocoloAno', 'desc');
        $usuarioIDApagou->where('protocoloCompleto', '=', $protocoloCompleto);
        $usuarioIDApagou = $usuarioIDApagou->first();


        //verifica se a publicação é nula!
        //se não, verifica se o usuario é comum e esta tentando entrar com protocolo de uma publicação que não é dele

        if($usuarioIDApagou != null){
            if(!Gate::allows('administrador', Auth::user()) && Auth::user()->id != $usuarioIDApagou->usuarioID){
                return redirect('/home')->with('erro', 'Você não tem permissão!');
              }
            // Busca todos os dados da visualização

            $publicacao = Publicacao::orderBy('protocoloAno', 'desc')->orderBy('protocolo', 'desc');

            if($usuarioIDApagou->usuarioIDApagou != null){
                $publicacao->join('users as apagado', 'apagado.id', 'publicacao.usuarioIDApagou');
            }

            $publicacao->join('users as criado', 'criado.id', 'publicacao.usuarioID');
            $publicacao->join('diariodata', 'diariodata.diarioDataID', 'publicacao.diarioDataID');
            $publicacao->join('situacao', 'situacao.situacaoID', 'publicacao.situacaoID');
            $publicacao->join('caderno', 'caderno.cadernoID', 'publicacao.cadernoID');
            $publicacao->join('tipodocumento', 'tipodocumento.tipoID', 'publicacao.tipoID');
            $publicacao->join('orgaorequisitante', 'orgaorequisitante.orgaoID', 'criado.orgaoID');
            $publicacao->where('protocoloCompleto', '=', $protocoloCompleto);
            if($usuarioIDApagou->usuarioIDApagou != null){
                $publicacao->select('publicacao.*', 'caderno.cadernoNome', 'tipodocumento.tipoDocumento', 'diariodata.*', 'situacao.*', 'criado.name as nomeUsuarioCriado', 'apagado.name as nomeUsuarioApagado', 'orgaorequisitante.orgaoNome');
            }else{
                $publicacao->select('publicacao.*', 'caderno.cadernoNome', 'tipodocumento.tipoDocumento', 'diariodata.*', 'situacao.*', 'criado.name as nomeUsuarioCriado', 'orgaorequisitante.orgaoNome');
            }
            $publicacao = $publicacao->first();

        }else{
            return redirect('/home')->with('erro', 'Não existe publicação com esse protocolo!');
        }


        // pega a url voltar e salva
        if(url()->previous() != url()->current()){
            Session::put('urlVoltar', url()->previous());
        }


        return view('publicacao.ver', ['publicacao' => $publicacao]);
    }


    // Download de Publicações pelo protocolo
    public function download($protocolo){

        $publicacao = Publicacao::orderBy('protocoloAno', 'desc');
        $publicacao->join('caderno', 'caderno.cadernoID', 'publicacao.cadernoID');
        $publicacao->join('tipodocumento', 'tipodocumento.tipoID', 'publicacao.tipoID');
        $publicacao->join('users as criado', 'criado.id', 'publicacao.usuarioID');
        $publicacao->join('diariodata', 'diariodata.diarioDataID', 'publicacao.diarioDataID');

        if($protocolo != null){
            if(strlen($protocolo) > 7){
                $publicacao->where('protocoloCompleto', '=', $protocolo);
            }else{
                $publicacao->where('protocolo', '=', null);
            }
        }else{
            $publicacao->where('protocolo', '=', null);
        }

        $publicacao = $publicacao->first();

        if($publicacao == null){

            return redirect()->back()->with('erro', 'Protocolo não encontrado!');
        }

        if($publicacao->usuarioIDApagou != null){
            return redirect()->back()->with('erro', 'Arquivo não encontrado!');
        }

        if ($publicacao != null) {
            if(!Gate::allows('administrador', Auth::user()) && Auth::user()->id != $publicacao->usuarioID){
                return redirect()->back()->with('erro', 'Você não tem permissão!');
            }
        }else{
            return redirect()->back()->with('erro', 'Arquivo não encontrado!');
        }

        $arquivoExtensao = explode('.', $publicacao->arquivo);

        if(file_exists(storage_path("app/".$publicacao->arquivo))){
            return Response::download(storage_path("app/".$publicacao->arquivo), ''.$protocolo.'-'.$publicacao->name.'-'.'Diario-'.$publicacao->numeroDiario.'.'.$arquivoExtensao[1].'');
        }else{
            return redirect()->back()->with('erro', 'Arquivo não Encontrado!');
        }

    }


    // função para aceitar uma publicação
    public function aceitar(Request $request){
        $protocolo = $request->protocolo;
        $publicacao = Publicacao::orderBy('protocoloAno', 'desc');

        if($protocolo != null){
            if(strlen($protocolo) > 7){
                $publicacao->where('protocoloCompleto', '=', $protocolo);
            }else{
                return redirect()->back()->with(['erro' => 'Publicação não encontrada!']);
            }
        }else{
            return redirect()->back()->with(['erro' => 'Publicação não encontrada!']);
        }

        $publicacao->update(['situacaoID' => 3]);
        return redirect()->to(Session::get('urlVoltar'))->with('sucesso', 'Publicação Aceita!');
    }


    //Função para apagar uma publicação
    public function apagar(Request $request){

        $protocolo = $request->protocolo;
        $publicacao = Publicacao::orderBy('protocoloAno', 'desc');

        if($protocolo != null){
            if(strlen($protocolo) > 7){
                $publicacao->where('protocoloCompleto', '=', $protocolo);
            }else{
                return redirect()->back()->with('erro', 'Publicação não encontrada!');
            }
        }else{
            return redirect()->back()->with('erro', 'Publicação não encontrada!');
        }

        // verifica se existe o arquivo e o deleta;

        if(file_exists(storage_path("app/".$request->arquivo))){
            Storage::delete([$request->arquivo]);
        }

        $publicacao->update(['situacaoID' => 2, 'usuarioIDApagou' => Auth::user()->id, 'dataApagada' => date('Y-m-d H:i:s')]);
        return redirect()->back()->with('sucesso', 'Publicação Apagada!');

    }

    // função para publicar
    public function publicar(Request $request){
        $protocolo = $request->protocolo;
        $publicacao = Publicacao::orderBy('protocoloAno', 'desc');

        if($protocolo != null){
            if(strlen($protocolo) > 7){
                $publicacao->where('protocoloCompleto', '=', $protocolo);
            }else{
                return redirect()->back()->with('erro', 'Publicação não encontrada!');
            }
        }else{
            return redirect()->back()->with('erro', 'Publicação não encontrada!');
        }

        $publicacao->update(['situacaoID' => 1]);
        return redirect()->back()->with('sucesso', 'Publicação Publicada!');
    }


    // função para publicar
    public function Rejeitar(Request $request){

        $protocolo = $request->protocolo;
        $publicacao = Publicacao::orderBy('protocoloAno', 'desc');

        if($protocolo != null){
            if(strlen($protocolo) > 7){
                $publicacao->where('protocoloCompleto', '=', $protocolo);
            }else{
                return redirect()->back()->with(['erro' => 'Publicação não encontrada!']);
            }
        }else{
            return redirect()->back()->with(['erro' => 'Publicação não encontrada!']);
        }

        if(strlen($request->descricao) >= 255){
            return redirect()->back()->with(['erro' => 'Tamanho da descrição excedida!']);
        }

        $publicacao->update(['situacaoID' => 5, 'rejeitadaDescricao' => $request->descricao]);
        return redirect()->to(Session::get('urlVoltar'))->with('sucesso', 'Publicação Rejeitada!');
    }
}