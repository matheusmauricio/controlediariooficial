<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\User;
use App\Comunicado;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\GrupoUsuario;


class ComunicadoController extends Controller
{
    //

    private $paginacao = 10;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function listarFiltro(Request $request){

        $filtro = str_replace('/', '¨', $request->tituloMensagem);
        return redirect()->route('listarComunicados', ['filtro' => $filtro]);
    }

    public function listar($filtro = null){

        $comunicados = Comunicado::orderBy('dataComunicado', 'desc');
        $comunicados->join('users', 'users.id', 'comunicado.usuarioID');



        if(!Gate::allows('administrador', Auth::user())){
            $comunicados->join('comunicadousuario', 'comunicadousuario.comunicadoID', 'comunicado.comunicadoID');
            $comunicados->where('comunicadousuario.usuarioID', '=', Auth::user()->id);
        }
        $comunicados->select('comunicado.*', 'users.name as nomeUsuario');

        if($filtro != null){
            $filtro = str_replace('¨', '/', $filtro);
            $arrayPalavras = explode(' ', $filtro);
            foreach ($arrayPalavras as $palavra) {
                $comunicados->where('comunicado.tituloMensagem', 'like', '%' . $palavra . '%');
            }
        }

        $comunicados = $comunicados->paginate($this->paginacao);

        return view('comunicado.listar', ['comunicados' => $comunicados]);


    }


    public function cadastrar(){
        if(Gate::allows('administrador', Auth::user()) ){
            $grupos = DB::table('grupousuario')->orderBy('grupoDescricao')->get();
            return view('comunicado.cadastrar', ['grupos' => $grupos]);
        }else{
            return redirect('home')->with('erro', 'Você não tem permissão para isso!');
        }
    }


    public function salvar(Request $request){

        switch ($this->validar($request)){

            case 1:
                return redirect()->back()->with('erro', "Mensagen com tamanho excedido!");
            break;

            case 2:
                return redirect()->back()->with('erro', "Mensagen deve ser destinada para no mínimo 1 grupo de usuário!");
            break;

            case 3:
                return redirect()->back()->with('erro', "Título com tamanho excedido!");
            break;

            default:
                $grupos = json_decode($request->grupos);

                if(isset($request->comunicadoID)){

                    DB::beginTransaction();

                    DB::table('comunicado')->where('comunicadoID', '=', $request->comunicadoID)->update(['mensagem' => $request->mensagem, 'tituloMensagem' => $request->tituloMensagem, 'dataComunicado' => date('Y-m-d H:i:s'), 'usuarioID' => Auth::user()->id]);
                    DB::table('comunicadousuario')->where('comunicadoID', '=', $request->comunicadoID)->delete();
                    DB::table('comunicadogrupousuario')->where('comunicadoID', '=', $request->comunicadoID)->delete();



                    $users = User::orderBy('name');

                        $contadorWhere = 0;

                        foreach($grupos as $grupo){

                            DB::table('comunicadogrupousuario')->insert(['grupoID' => $grupo->grupoID,'comunicadoID' => $request->comunicadoID]);
                            if($contadorWhere == 0){
                                $users->where('grupoID', '=', $grupo->grupoID);
                            }else{
                                $users->orWhere('grupoID', '=', $grupo->grupoID);
                            }

                            $contadorWhere++;
                        }

                        $users = $users->get();

                        foreach ($users as $user) {
                            DB::table('comunicadousuario')->insert(['usuarioID' => $user->id,'comunicadoID' => $request->comunicadoID]);
                        }
                        DB::table('log')->orderBy('logData')->insert(['logData' => date('Y-m-d H:i:s'), 'usuarioID' =>  Auth::user()->id , 'logDescricao' => 'Usuario: '.Auth::user()->name.'(id:'.Auth::user()->id.')  Editou o comunicado '.$request->tituloMensagem]);

                        DB::commit();
                        return redirect('/comunicado/listar')->with('sucesso', 'Comunicado Editado!');

                }else{

                    try {

                        DB::beginTransaction();

                        DB::table('comunicado')->insert(['mensagem' => $request->mensagem, 'tituloMensagem' => $request->tituloMensagem, 'dataComunicado' => date('Y-m-d H:i:s'), 'usuarioID' => Auth::user()->id]);
                        $comunicadoID = DB::table('comunicado')->max('comunicadoID');

                        $users = User::orderBy('name');

                        $contadorWhere = 0;

                        foreach($grupos as $grupo){

                            DB::table('comunicadogrupousuario')->insert(['grupoID' => $grupo->grupoID,'comunicadoID' => $comunicadoID]);
                            if($contadorWhere == 0){
                                $users->where('grupoID', '=', $grupo->grupoID);
                            }else{
                                $users->orWhere('grupoID', '=', $grupo->grupoID);
                            }

                            $contadorWhere++;
                        }

                        $users = $users->get();

                        foreach ($users as $user) {
                            DB::table('comunicadousuario')->insert(['usuarioID' => $user->id,'comunicadoID' => $comunicadoID]);
                        }

                        DB::table('log')->orderBy('logData')->insert(['logData' => date('Y-m-d H:i:s'), 'usuarioID' =>  Auth::user()->id , 'logDescricao' => 'Usuario: '.Auth::user()->name.'(id:'.Auth::user()->id.')  Cadastrou o comunicado '.$request->tituloMensagem]);

                        DB::commit();
                        return redirect('/comunicado/listar')->with('sucesso', 'Comunicado Enviado!');

                    } catch (\Exception $e) {

                        DB::rollBack();

                        return redirect()->back()->with('erro', 'Ocorreu um erro durante o envio do comunicado! erro: '.$e->getMessage());
                    }

                }
            break;
        }

    }

    public function validar($request){

        if(strlen($request->mensagem) > 255){
            return 1;
        }

        if(strlen($request->tituloMensagem) > 100){
            return 3;
        }

        if($request->grupos == "[]"){
            return 2;
        }

    }



    public function editar($id){

        if(Gate::allows('administrador', Auth::user())){

            $comunicado = Comunicado::orderBy('dataComunicado')->where('comunicadoID', '=', $id)->first();

            $grupoUsuarioComunicado = Comunicado::orderBy('comunicado.comunicadoID');
            $grupoUsuarioComunicado->join('comunicadogrupousuario', 'comunicadogrupousuario.comunicadoID', 'comunicado.comunicadoID');
            $grupoUsuarioComunicado->join('grupousuario', 'grupousuario.grupoID', 'comunicadogrupousuario.grupoID');
            $grupoUsuarioComunicado->select('grupousuario.*');
            $grupoUsuarioComunicado->where('comunicado.comunicadoID', '=', $id);
            $grupoUsuarioComunicado = $grupoUsuarioComunicado->get();


            // dd($grupoUsuarioComunicado);

            $gruposUsuario = GrupoUsuario::orderBy('grupoDescricao');
            $gruposUsuario->select('grupoID', 'grupoDescricao');
            foreach($grupoUsuarioComunicado as $grupoUsuario){
                $gruposUsuario->where('grupoID', '!=', $grupoUsuario->grupoID);
            }
            $gruposUsuario = $gruposUsuario->get();

            // dd($grupoUsuarioComunicado);
            return view('comunicado.editar', ['comunicado' => $comunicado, 'grupoUsuarioComunicado' => $grupoUsuarioComunicado, 'gruposUsuario' => $gruposUsuario]);

        }else{

            return redirect('home')->with('erro', 'Você não tem permissão para isso!');

        }
    }


    public function deletar($id){

        if(Gate::allows('administrador', Auth::user())){

            try {

                DB::beginTransaction();
                DB::table('comunicado')->where('comunicadoID', '=', $id)->delete();
                DB::table('comunicadousuario')->where('comunicadoID', '=', $id)->delete();
                DB::table('comunicadogrupousuario')->where('comunicadoID', '=', $id)->delete();
                DB::table('log')->orderBy('logData')->insert(['logData' => date('Y-m-d H:i:s'), 'usuarioID' =>  Auth::user()->id , 'logDescricao' => 'Usuario: '.Auth::user()->name.'(id:'.Auth::user()->id.')  deletou o comunicado de id '.$id]);
                DB::commit();

                return redirect('/comunicado/listar')->with('sucesso', 'Comunicado Deletado!');

            } catch (\Exception $e) {

                DB::rollBack();
                return redirect()->back()->with('erro', 'Ocorreu um erro durante o envio do comunicado! erro: '.$e->getMessage());

            }

        }else{

            return redirect('home')->with('erro', 'Você não tem permissão para isso!');

        }

    }

    public function verificarComunicados(){
        $comunicados = Comunicado::orderBy('dataComunicado', 'desc');
        $comunicados->join('comunicadousuario', 'comunicadousuario.comunicadoID', 'comunicado.comunicadoID');
        $comunicados->join('users', 'users.id', 'comunicadousuario.usuarioID');
        $comunicados->where('comunicadousuario.usuarioID', '=', Auth::user()->id);
        $comunicados->where('comunicadousuario.visualizado', '=', 0);
        $comunicados->select('comunicado.*', 'users.name as nomeUsuario');
        $comunicados = $comunicados->get();
        return $comunicados;
    }


    public function visualizarComunicado(Request $request){
        try {
            DB::beginTransaction();

            DB::table('comunicadousuario')->where('comunicadoID', '=', $request->comunicadoID)->where('usuarioID', '=', Auth::user()->id)->update(['visualizado' => 1]);

            DB::commit();
            return redirect()->back();
        } catch (\Exception $e) {

            DB::rollBack();
            return redirect()->back()->with('erro', 'Ocorreu um erro durante aceitar o comunicado! erro: '.$e->getMessage());
        }

    }


    public function ver($id){

        $comunicado = Comunicado::orderBy('dataComunicado')->where('comunicadoID', '=', $id);
        $comunicado->join('users', 'users.id', 'comunicado.usuarioID');
        $comunicado = $comunicado->first();
        if($comunicado != null){
            return view('comunicado.ver', ['comunicado' => $comunicado]);
        }else{
            return redirect('home')->with('erro', 'Comunicado não encontrado!');
        }

    }


}
