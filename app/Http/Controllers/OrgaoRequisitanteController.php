<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\OrgaoRequisitante;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use App\User;
use Illuminate\Support\Facades\DB;
use App\Publicacao;

class OrgaoRequisitanteController extends Controller
{
    //
    private $paginacao = 10;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function listar(){

        if(Gate::allows('administrador', Auth::user()) || Gate::allows('publicador', Auth::user())){

            $orgaosRequisitantes = OrgaoRequisitante::orderBy('orgaoNome', 'desc');
            $orgaosRequisitantes = $orgaosRequisitantes->paginate($this->paginacao);
            return view('orgaorequisitante.listar', ['orgaosRequisitantes' => $orgaosRequisitantes]);

        }else{
            return redirect('/home');
        }
    }


    public function cadastrar(){
        if(Gate::allows('administrador', Auth::user()) || Gate::allows('publicador', Auth::user())){
            return view('orgaorequisitante.cadastrar');
        }else{
            return redirect('/home');
        }
    }


    public function salvar(Request $request){

        $orgaoRequisitante = OrgaoRequisitante::orderBy('orgaoNome');

        switch ($this->validar($request)){

            case 1:
                return redirect()->back()->with("erro", "Órgão Requisitante já existente")->withInput();
            break;

            default:

                if(isset($request->orgaoID)){
                    $orgaoRequisitante->where('orgaoID', '=', $request->orgaoID)->update(['orgaoNome' => $request->orgaoNome]);
                    DB::table('log')->orderBy('logData')->insert(['logData' => date('Y-m-d H:i:s'), 'usuarioID' =>  Auth::user()->id , 'logDescricao' => 'Usuario: '.Auth::user()->name.'(id:'.Auth::user()->id.')  Editou o Órgão Requisitante '.$request->orgaoNome]);
                    return redirect('/orgaorequisitante/listar')->with("sucesso", "Órgão Requisitante Editado");
                }else{
                    DB::table('log')->orderBy('logData')->insert(['logData' => date('Y-m-d H:i:s'), 'usuarioID' =>  Auth::user()->id , 'logDescricao' => 'Usuario: '.Auth::user()->name.'(id:'.Auth::user()->id.')  Cadastrou o Órgão Requisitante '.$request->orgaoNome]);
                    $orgaoRequisitante->insert(['orgaoNome' => $request->orgaoNome]);
                    return redirect('/orgaorequisitante/listar')->with("sucesso", "Órgão Requisitante Cadastrado");
                }

            break;

        }

    }

    public function editar($id){

        if(Gate::allows('administrador', Auth::user()) || Gate::allows('publicador', Auth::user())){

            $orgaoRequisitante = OrgaoRequisitante::orderBy('orgaoID');
            $orgaoRequisitante->select('*')->where('orgaoID', '=', $id);
            $orgaoRequisitante = $orgaoRequisitante->first();

            $users = User::orderBy('name');
            $users->select('name');
            $users->where('orgaoID', '=', $id);
            $users = $users->get();


            return view('orgaorequisitante.editar', ['orgaoRequisitante' => $orgaoRequisitante, 'usuarios' => $users]);

        }else{
            return redirect('/home');
        }
    }

    public function validar($orgaoRequisitante){

        $verificador = OrgaoRequisitante::orderBy('orgaoNome');

        if(isset($orgaoRequisitante->orgaoID)){

            if( $verificador->select('*')->where('orgaoNome', '=', $orgaoRequisitante->orgaoNome)->where('orgaoID', '!=', $orgaoRequisitante->orgaoID)->count()){

                return 1;

            }

        }else{

            if( $verificador->select('*')->where('orgaoNome', '=', $orgaoRequisitante->orgaoNome)->count()){

                return 1;

            }

        }

    }


    public function deletar($id){

        if(Gate::allows('administrador', Auth::user()) || Gate::allows('publicador', Auth::user())){

            $orgaoRequisitante = OrgaoRequisitante::orderBy('orgaoID');


            $users = User::orderBy('id');
            $users = $users->where('orgaoID', '=', $id)->get();

            // validar deletar OBS**

            if(sizeof($users) == 0){
                $publicacoes = Publicacao::orderBy('dataEnvio');

                if($publicacoes->where('orgaoID', '=', $id)->count()){
                    return redirect()->back()->with(["erro" => "Impossível deletar pois existem publicações vinculadas a este Órgão Requisitante!"]);
                }


                $orgaoRequisitante->where('orgaoID', '=', $id)->delete();
                DB::table('log')->orderBy('logData')->insert(['logData' => date('Y-m-d H:i:s'), 'usuarioID' =>  Auth::user()->id , 'logDescricao' => 'Usuario: '.Auth::user()->name.'(id:'.Auth::user()->id.')  Deletou o Órgão Requisitante de id '.$id]);
                return redirect('/orgaorequisitante/listar')->with("sucesso", "Órgão Requisitante Deletado");

            }else{
                return redirect()->back()->with(["erro" => "Impossível deletar pois existem usuários vinculados a este Órgão Requisitante!", 'usuarios' => $users]);
            }

        }else{
            return redirect('/home');
        }
    }



}
