@extends('layouts.app')

@section('content')

<div id="Sucesso" class="container">
    <div class="col-md-8 offset-md-2">
        @if(session()->has('sucesso'))
            <br>
            <div class="form-group row mb-0 alert alert-success" style="font-size:20px">
                {{ session()->get('sucesso') }}
            </div>
        @endif
        </div>
    </div>
</div>

<div id="erro" class="container">
        <div class="col-md-8 offset-md-2">
            @if(session()->has('erro'))
                <br>
                <div class="form-group row mb-0 alert alert-danger" style="font-size:20px">
                    {{ session()->get('erro') }}
                </div>
            @endif
            </div>
        </div>
    </div>



    {{-- Apagar Depois --}}
    {{-- <div id="erro" class="container">
        <div class="col-md-8 offset-md-2">
                <br>
                <div class="form-group row mb-0 alert alert-danger" style="font-size:20px">
                        A area de publicação não estará funcionando corretamente durante o período desta mensagem!
                </div>
            </div>
        </div>
    </div> --}}



<br>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">DIÁRIO OFICIAL CACHOEIRO DE ITAPEMIRIM</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif
                    <p> Bem vindo </p>
                    <span>Hoje é <strong>{{date('d/m/Y')}}</strong></span>
                    <span style="margin-left:2%;">Horário Atual: <strong>{{date('H:i')}}</strong></span>


                </div>
            </div>
            @if ($diarioData != null)
                <br>
                <div class="card" style="color:red; border-color:red;">
                    <div class="card-header" style="text-align:center; border-color:red;">ATENÇÃO PARA O HORÁRIO</div>

                    <div class="card-body">

                    @php
                        $dataDiario = new DateTime($diarioData->diarioData);
                        $dataDiario = $dataDiario->format('d/m/Y');

                        $dataLimite = new DateTime($diaLimite);
                        $dataLimite = $dataLimite->format('d/m/Y');
                    @endphp
                    <p> PARA O DIÁRIO <b style="text-transform:uppercase;">{{$diarioData->numeroDiario}} - {{$dataDiario}}</b> SOMENTE É POSSÍVEL ENVIAR PUBLICAÇÕES ATÉ AS <b>{{Auth::user()->horaEnvio}}</b> HORAS DO DIA <b>{{$dataLimite}}</b></p>

                    </div>
                </div>
            @endif
        </div>

        <div class="col-md-4">
            <div class="card">
                    <div class="card-header" style="text-align:center;"> <b> Lista de Feriados / Pontos Facultativos </b></div>
                    <div class="card-body">
                            <div class="table-responsive">
                                <table id="mytable" class="table table-bordred table-striped">

                                        <thead>
                                        <th>Data</th>
                                        <th>Descrição</th>
                                        </thead>
                                        <tbody>

                                             @foreach ($diasNaoUteis as $dia)

                                                @php
                                                    $data = new DateTime($dia->diaNaoUtilData);
                                                    $data = $data->format('d/m/Y');
                                                @endphp

                                             <tr>
                                                <td>{{$data}}</td>
                                                <td>{{$dia->diaDescricao}}</td>
                                            </tr>

                                             @endforeach

                                            </tbody>

                                     </table>

                            </div>
                    </div>
            </div>
        </div>

    </div>
</div>

@endsection
