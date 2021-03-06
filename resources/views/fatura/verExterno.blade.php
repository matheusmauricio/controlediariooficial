@extends('layouts.app')
@section('content')

@auth

<div id="Erro" class="container">
        <div class="col-md-8 offset-md-2">
            @if(session()->has('erro'))
                <br>
                <div class="form-group row mb-0 alert alert-danger" style="font-size:20px">
                    {{ session()->get('erro') }}
                </div>
            @endif
            </div>
</div>

<br>

<div id="DAM" class="container">
    <div class="col-md-8 offset-md-2">
        @if(session()->has('DAM'))
            <br>
            <div class="form-group row mb-0 alert alert-success" style="font-size:20px">
                {{ session()->get('DAM') }}
            </div>
        @endif
    </div>
</div>

<br>

<div class="container" id="pagina">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card">
                <div class="card-header"> {{ __('Fatura') }}</div>

                <div class="card-body">

                            <div class="form-group row">
                                <div class="col-md-10">
                                    <p>Protocolo: <strong>{{$fatura->protocoloCompleto}} </strong></p>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-10">
                                    <p>Empresa: <strong>{{$fatura->empresa}} </strong></p>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-10">
                                    <p>Requisitante: <strong>{{$fatura->requisitante}} </strong></p>
                                </div>
                            </div>

                            @if ($fatura->email != null)
                                <div class="form-group row">
                                    <div class="col-md-10">
                                        <p>Email: <strong>{{$fatura->email}} </strong></p>
                                    </div>
                                </div>
                            @endif

                            @if ($fatura->telefoneFixo != null)
                                <div class="form-group row">
                                    <div class="col-md-10">
                                        <p>Telefone Fixo: <strong>{{$fatura->telefoneFixo}}</strong></p>
                                    </div>
                                </div>
                            @endif

                            @if ($fatura->telefoneCelular != null)

                                <div class="form-group row">
                                    <div class="col-md-10">
                                        <p>Telefone Celular: <strong>{{$fatura->telefoneCelular}}</strong></p>
                                    </div>
                                </div>
                            @endif

                            @php
                                // Calculo da mascara do cpf ou cnpj

                                if(strlen($fatura->cpfCnpj) > 11){
                                    $mask = '##.###.###/####-##';
                                }else{
                                    $mask ='###.###.###-##';
                                }

                                $val = $fatura->cpfCnpj;
                                $maskared = '';
                                $k = 0;
                                for($i = 0; $i<=strlen($mask)-1; $i++)
                                {
                                    if($mask[$i] == '#')
                                    {
                                        if(isset($val[$k]))
                                            $maskared .= $val[$k++];
                                    }
                                    else
                                    {
                                        if(isset($mask[$i]))
                                            $maskared .= $mask[$i];
                                    }
                                }

                            @endphp

                            <div class="form-group row">
                                <div class="col-md-10">
                                    <p>CPF / CNPJ: <strong>{{$maskared}}</strong> </p>
                                </div>
                            </div>

                            @php
                                $dataEnvio = new DateTime($fatura->dataEnvioFatura);
                                $dataEnvio = $dataEnvio->format('d/m/Y');
                            @endphp
                            <div class="form-group row">
                                <div class="col-md-10">
                                    <p>Data Envio: <strong>{{$dataEnvio}}</strong> </p>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-12">
                                    <p style="float:left !important;">Usuário Que Emitiu: <strong style="text-transform:capitalize;">{{$fatura->usuarioNome}}</strong> </p>
                                    <button class="btn btn-primary" data-toggle="modal" data-target="#modalContatos" style="margin-left:2%;">Contato</button>
                                </div>
                            </div>


                            <div class="form-group row">

                                <div class="col-md-10">
                                    <span>Observações: </span>
                                </div>
                                <div class="col-md-8">
                                        @if(session()->has('sucesso'))

                                            <div class="alert alert-success" style="font-size:15px;">
                                                {{ session()->get('sucesso') }}
                                            </div>
                                        @endif
                                </div>
                                @if (Gate::allows('administrador', Auth::user()) && $fatura->situacaoID == 4)

                                    <form action="{{ url("/fatura/editarAnotacao") }}" method="POST">
                                        @csrf
                                        <div class="col-md-10">
                                            <textarea cols="60" rows="4" class="form-control" name="observacao"> {{$fatura->observacao}} </textarea>
                                            <input type="hidden" name="protocolo" value="{{$fatura->protocoloCompleto}}">
                                            <input type="submit" value="Adicionar Anotação" name="enviar" class="btn btn-primary" style="margin-top:2%; margin-left:2%;">
                                        </div>

                                    </form>
                                @else
                                    <div class="col-md-10">
                                        <textarea disabled cols="60" rows="4" class="form-control"> {{$fatura->observacao}} </textarea>
                                    </div>
                                @endif

                            </div>

                            <div class="form-group row">
                                <div class="col-md-10">
                                    <p>Matéria: <strong>{{$fatura->tipoDocumento}}</strong> </p>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-10">
                                    <p>Subcategoria: <strong> @if($fatura->subcategoriaNome != null) {{$fatura->subcategoriaNome}} @else Não Possui @endif </strong> </p>
                                </div>
                            </div>

                            @if($fatura->diarioData != null)
                                @php
                                    $data = new DateTime($fatura->diarioData);
                                    $data = $data->format('d/m/Y');
                                @endphp

                                <div class="form-group row">
                                    <div class="col-md-10">
                                        <p>Diário: <strong> N° {{$fatura->numeroDiario}}  Data: {{$data}}</strong> </p>
                                    </div>
                                </div>
                            @else

                                <div class="form-group row">
                                    <div class="col-md-10">
                                        <p>Diário: <strong> Não Possui </strong> </p>
                                    </div>
                                </div>

                            @endif

                            <div class="form-group row">
                                <div class="col-md-10">
                                    <p>Largura Coluna: <strong>{{$fatura->largura}} cm </strong> </p>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-10">
                                    <p>Valor Coluna: <strong>R$ {{$fatura->valorColuna}} </strong> </p>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-10">
                                    <p>Centimetragem: <strong>{{$fatura->centimetragem}} cm </strong> </p>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-10">
                                    <p>Valor da Fatura: <strong>R$ {{$fatura->valor}} </strong> </p>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-10">
                                    <p>Situação: 
                                        <strong> 
                                            @if ($fatura->situacaoNome == "Aceita")
                                                Paga
                                            @else
                                                @if ($fatura->situacaoNome == "Enviada")
                                                    Cadastrada
                                                @else
                                                    {{$fatura->situacaoNome}}
                                                @endif
                                            @endif
                                        </strong>
                                    </p>
                                </div>
                            </div>

                            @if ($fatura->situacaoNome == "Apagada")

                                <div class="form-group row">
                                    <div class="col-md-10">
                                            <p>Apagada por: <strong>{{$fatura->usuarioNomeApagou}}</strong> </p>
                                    </div>
                                </div>

                            @endif

                            @if ($fatura->situacaoNome == "Publicada")

                                <div class="form-group row">
                                    <div class="col-md-10">
                                            <p>Publicada por: <strong>{{$fatura->usuarioNomePublicou}}</strong> </p>
                                    </div>
                                </div>

                            @endif


                            @if ($fatura->situacaoNome == "Rejeitada")

                                <div class="form-group row">
                                    <div class="col-md-10">
                                        <span>Descrição: </span>
                                    </div>
                                    <div class="col-md-10">
                                        <textarea disabled cols="60" rows="4" class="form-control"> {{$fatura->descricaoCancelamento}} </textarea>
                                    </div>
                                </div>

                            @endif


                            @if ($fatura->dam == null && (Gate::allows('faturas', Auth::user()) || Gate::allows('administrador', Auth::user()) || Gate::allows('publicador', Auth::user())) && $fatura->situacaoID == 4)

                                <form action="{{ url("/fatura/anexarDam") }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="form-group row">
                                        <div class="col-md-10">
                                            <p><b> Anexar DAM: </b></p>
                                        </div>
                                        <div class="col-md-10">
                                            <input type="file" name="arquivo" required>
                                        </div>
                                        <div class="col-md-10">
                                            <strong><sub style="font-size:90%;">Tamanho máximo: 30 MB</sub></strong>
                                            <input type="hidden" name="protocolo" value="{{$fatura->protocoloCompleto}}">
                                        </div>
                                        <div class="col-md-10">
                                            <input type="submit" value="Anexar" name="enviar" class="btn btn-primary" style="margin-top:2%;">
                                            <a target="_blank" href="http://www.cachoeiro.es.gov.br/servicos/site.php?nomePagina=SERDAMTP" class="btn btn-primary" style="color:white; margin-top:2%;" >Emitir DAM</a>
                                        </div>
                                    </div>
                                </form>

                            @endif

                            <div class="form-group row">

                                @php
                                    $modalPublicar = false;
                                    $modalAceitar = false;
                                    $modalRejeitar = false;
                                @endphp

                                @if ( ($fatura->situacaoNome == "Enviada" || $fatura->situacaoNome == "Rejeitada") && ( Gate::allows('administrador', Auth::user()) ||  Gate::allows('publicador', Auth::user()) ))
                                    @php
                                        $modalAceitar = true;
                                    @endphp
                                    <div class="col-md-2" style="min-width:100px;">
                                        <a class="btn btn-success" style="width:100px; color:azure; " data-toggle="modal" data-target="#modalAceitar">Aceitar</a>
                                    </div>
                                @endif

                                @if ( ($fatura->situacaoNome == "Enviada" || $fatura->situacaoNome =="Aceita")  && ( Gate::allows('administrador', Auth::user()) ||  Gate::allows('publicador', Auth::user()) ) )
                                    @php
                                        $modalRejeitar = true;
                                    @endphp
                                    <div class="col-md-2" style="min-width:100px;">
                                        <a class="btn btn-danger" style="width:100px; color:azure; " data-toggle="modal" data-target="#modalRejeitar">Rejeitar</a>
                                    </div>
                                @endif

                                @if ($fatura->situacaoNome == "Aceita" && ( Gate::allows('administrador', Auth::user()) ||  Gate::allows('publicador', Auth::user()) ))
                                    @php
                                        $modalPublicar = true;
                                    @endphp
                                    <div class="col-md-2" style="min-width:100px;">
                                        <a class="btn btn-success" style="width:100px; color:azure; " data-toggle="modal" data-target="#modalPublicar">Publicar</a>
                                    </div>
                                @endif

                            </div>

                            <br>

                            <div class="form-group row">
                                <div class="col-md-6 offset-md-4">
                                    <a href="{{ url("/fatura/downloadOriginal") }}/{{$fatura->protocoloCompleto}}" class="btn btn-primary" style="width:150px; float:right;">Arquivo Original</a>
                                </div>
                            </div>

                            @if ($fatura->situacaoNome == "Aceita" || $fatura->situacaoNome == "Publicada" )
                                <div class="form-group row">
                                    <div class="col-md-6 offset-md-4">
                                        <a href="{{ url("/fatura/downloadComprovantePago") }}/{{$fatura->protocoloCompleto}}" style="width:150px; float:right;" class="btn btn-primary">Comprovante Pago</a>
                                    </div>
                                </div>
                            @endif

                            @if ($fatura->dam != null)
                                <div class="form-group row">
                                    <div class="col-md-6 offset-md-4">
                                        <a href="{{ url("/fatura/downloadDAM") }}/{{$fatura->protocoloCompleto}}" style="width:150px; float:right;" class="btn btn-primary">Download DAM</a>
                                    </div>
                                </div>
                            @endif
                                
                            <div class="form-group row">
                                <div class="col-md-6 offset-md-4">
                                    <a href="{{ url("/fatura/gerarComprovante") }}/{{$fatura->protocoloCompleto}}"  target="_blank" class="btn btn-primary" style="width:150px; float:right;">Comprovante Envio</a>
                                </div>
                            </div>

                            <br>

                            <div style="float:right; margin-bottom:1%;">
                                <button type="button" class="btn btn-primary" id="btnVoltar">
                                    Voltar
                                </button>
                            </div>

                </div>

            </div>
        </div>
        
    </div>


{{-- verifica se possui modal aceitar --}}

@if ($modalAceitar)
        <form id="formAceitar" action="{{ url("/fatura/aceitar") }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="protocolo" value="{{$fatura->protocoloCompleto}}">
            {{-- situacao Aceita --}}
            <div class='modal fade' id="modalAceitar" role='dialog'>
                    <div class='modal-dialog row justify-content-center'>
                        <div class="modal-content">
                                <div class="modal-header">
                                    <Strong class=" offset-md-4" > Confirmar Pagamento </Strong>
                                </div>
                                <div class="modal-body">

                                    <p> Segue protocolo de fatura:</p>
                                    <b> {{$fatura->protocoloCompleto}} </b>

                                    <br><br>

                                    <strong style="font-size:12pt;">Insira o comprovante de pagamento:</strong>
                                    <input type="file" class="form-control-file" name="arquivo" id="file" required>
                                    <strong><sub style="font-size:90%;">Somente arquivos nas extensão 'PDF'.
                                    Tamanho máximo: 30 MB</sub></strong>

                                    <br> <br> <br>

                                    <p> Ao realizar esta ação, você confirma que houve pagamento dessa fatura!</p>
                                    <p><strong>Deseja realmente confirmar?</strong></p>

                                    <div>
                                            <div style="float: left;" class="offset-md-3">
                                                <div>
                                                    <button id="btnAceitar" class="btn btn-success" name="publicar">Confirmar</button>
                                                </div>
                                            </div>
                                            <div style="float: left; margin-left:2%;">
                                                <button id="btnDismiss"type="button" class="btn btn-secondary" data-dismiss="modal">
                                                    Voltar
                                                </button>
                                            </div>
                                    </div>
                                </div>
                        </div>
                    </div>
                </div>
            </form>
        @endif


{{-- verifica se possui modal Rejeitar --}}

@if ($modalRejeitar)
        <form id="formRejeitar" action="{{ url("/fatura/rejeitar") }}" method="POST" >
            @csrf
            <input type="hidden" name="protocolo" value="{{$fatura->protocoloCompleto}}">
            {{-- situacao Aceita --}}
            <div class='modal fade' id="modalRejeitar" role='dialog'>
                    <div class='modal-dialog row justify-content-center'>
                        <div class="modal-content">
                                <div class="modal-header">
                                    <Strong class=" offset-md-4" > Confirmar Rejeitar </Strong>
                                </div>
                                <div class="modal-body">

                                    <p> Segue protocolo de fatura:</p>
                                    <p> <b> {{$fatura->protocoloCompleto}} </b> </p>

                                    <p> <strong> Descreva o Motivo: </strong> </p>
                                    <textarea name="descricao" cols="60" rows="4" class="form-control" placeholder="Entre com a descrição!" style="resize: none;" value="{{old('descricao')}}" required></textarea>

                                    <br>
                                    <p> Ao realizar esta ação a fatura sera rejeitada, não podendo ser publicada!</p>
                                    <p><strong>Deseja realmente Rejeitar?</strong></p>

                                    <div>
                                            <div style="float: left;" class="offset-md-3">
                                                <div>
                                                    <button id="btnRejeitar"  class="btn btn-danger" name="publicar">Confirmar Rejeitar</button>
                                                </div>
                                            </div>
                                            <div style="float: left; margin-left:2%;">
                                                <button  type="button" class="btn btn-secondary" data-dismiss="modal">
                                                    Voltar
                                                </button>
                                            </div>
                                    </div>
                                </div>
                        </div>
                    </div>
                </div>
            </form>
@endif

@if ($modalPublicar)
    <form action="{{ url("/fatura/publicar") }}" method="POST">
        @csrf
        <input type="hidden" name="protocolo" value="{{$fatura->protocoloCompleto}}">
        {{-- situacao publicada --}}
        <div class='modal fade' id="modalPublicar" role='dialog'>
                <div class='modal-dialog row justify-content-center'>
                    <div class="modal-content">
                            <div class="modal-header">
                                <Strong class=" offset-md-4" > Confirmar Publicar </Strong>
                            </div>
                            <div class="modal-body">

                                    <div id="divLimite" style="display:none;">
                                            <br>
                                            <h4 id="textoLimite" style="text-align:center; color:red;">Texto</h4>
                                            <br>
                                    </div>


                                <p> Segue protocolo de fatura:</p>
                                <p> <b> {{$fatura->protocoloCompleto}} </b> </p>
                                <p> Ao realizar esta ação você confirma que a fatura será publicada no diário especificado.</p>


                                {{-- Escolher o Diário --}}
                                <div class="form-group row">
                                    @php
                                        $diariosDatas = json_decode($diarioDatas);
                                    @endphp
                                        <label for="diario" class="col-md-4 col-form-label text-md-right">{{ __('Diário') }} <span style="color:red;">*</span></label>
                                        <div class="col-md-6">
                                            <select id="diario" class="custom-select  mr-sm-2" name="diarioDataID" required onchange="dataLimite()">
                                                    <option slected value=""> Escolha o Diário </option>
                                                    @foreach ($diariosDatas as $item)
                                                        @php
                                                            $data = new DateTime($item->diarioData);
                                                            $data = $data->format('d/m/Y');
                                                        @endphp
                                                        <option  value="{{$item->diarioDataID}} "> N°{{$item->numeroDiario}} Data: {{$data}} </option>
                                                    @endforeach
                                            </select>
                                        </div>
                                    </div>

                                <p><strong>Deseja realmente Publicar?</strong></p>
                                <div>
                                        <div style="float: left; display:none;" class="offset-md-3" id="divBotao">
                                            <div>
                                                <input type="submit" class="btn btn-success" name="publicar" value="Confirmar Publicar">
                                            </div>
                                        </div>
                                        <div style="float: left;" class="offset-md-3" id="divLabel">
                                            <Strong><span style="color:red; white-space:nowrap;" id="labelText">Escolha um Diário!</span></Strong>
                                        </div>

                                        <div style="float: left; margin-left:2%;">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                                Voltar
                                            </button>
                                        </div>
                                </div>
                            </div>
                    </div>
                </div>
            </div>
    </form>
@endif



{{-- Modal Contato --}}
    <div class='modal fade' id="modalContatos" role='dialog'>
            <div class='modal-dialog row justify-content-center'>
                <div class="modal-content">
                        <div class="modal-header">
                            <Strong class=" offset-md-5" > Contatos </Strong>
                        </div>
                        <div class="modal-body">

                            <span>  Nome: </span>
                            <span> <b> {{$fatura->usuarioNome}} </b> </span>

                            <br><br>

                            <span style="float:left;">  Tefelone Celular: </span>
                            <input id="celular" type="text" readonly value="{{$fatura->telefoneCelularUsuarioEmitiu}}" style="background-color:transparent; border-color:transparent; margin-left:1%; font-weight:bold;">

                            <br><br>

                            <span>  Email: </span>
                            <span> <b> {{$fatura->emailUsuarioEmitiu}} </b> </span>

                            <br><br>

                            <span> Orgão Requisitante:</span>
                            <span> <b> {{$fatura->orgaoUsuarioEmitiu}} </b> </span>

                            <br><br>

                            <span>  Tefelone Setor: </span>
                            </b><input id="fixo" type="text" readonly value="{{$fatura->telefoneSetorUsuarioEmitiu}}" style="background-color:transparent; border-color:transparent; margin-left:1%; font-weight:bold;">

                            <br><br>

                            <div>
                                    <div style="float: left; margin-left:2%;">
                                        <button  type="button" class="btn btn-secondary" data-dismiss="modal">
                                            Voltar
                                        </button>
                                    </div>
                            </div>
                        </div>
                </div>
            </div>
        </div>

<div class="container" id="carregando" style="display:none;">
    <br><br>
    <h2 class="offset-md-4"> Carregando Solicitação </h2>
    <br>
    <div class="loader offset-md-5"></div>
</div>


<script type="text/javascript" src="{{ asset('js/jquery.mask.min.js') }}"></script>
<script>

    $(document).ready(function (){


        var diariosDiasLimites = <?php echo $diarioDatas; ?>;
        var url = "<?php  echo Session::get('urlVoltar');  ?>";

        $('#formAceitar').validate({
            errorClass: "my-error-class"
        });

        $("#fixo").mask('(99)9999-9999');
        $("#celular").mask('(99)9999-99999');

        $('#file').val("");
        var canUpload = false;
        $('#file').bind('change', function() {
            if( ((this.files[0].size / 1024)/1024) > 30){
                canUpload = false;
            }else{
                canUpload = true;
            }
        });

        $('#formAceitar').submit( function(e){
             if($("#formAceitar").valid()){
                 if(canUpload == true){
                    $("#btnDismiss").trigger("click");
                    $("#carregando").css('display', 'block');
                    $("#pagina").css('display', 'none');
                    $('#modalRejeitar').css('display', 'none');
                    $('#modalAceitar').css('display', 'none');
                    $('#Erro').css('display', 'none');
                    $('html, body').animate({scrollTop: '0px'}, 300);
                 }else{
                    event.preventDefault();
                    alert("Upload somente de arquivos até 30 MB!");
                 }

             }
         });

         $("#btnVoltar").click(function(){
                location.replace(url);
        })

        dataLimite = function(){
             if(!$("#diario").val() == ""){
                diariosDiasLimites.forEach(element => {

                    if(element.diarioDataID == $("#diario").val()){

                        var podeEnviar = false;
                        var diarioDeHoje = false;

                        var horaEnvio =  "<?php echo Auth::user()->horaEnvio; ?>";
                        horaEnvio = horaEnvio.split(':');
                        var horaAtual = "<?php echo date('H:i:s') ?>"

                        horaAtual = horaAtual.split(':');

                        var dataAtual = ("<?php echo date('Y-m-d') ?>").split('-');
                        var dataLimite = element.diaLimite.split('-');

                        dataAtual = new Date(dataAtual[0], dataAtual[1]-1, dataAtual[2]);
                        dataLimite = new Date(dataLimite[0], dataLimite[1]-1, dataLimite[2]);

                        if(dataLimite.getTime() > dataAtual.getTime()){
                            podeEnviar = true;
                        }else{
                            if(dataLimite.getTime() == dataAtual.getTime()){

                                if(horaAtual[0] > horaEnvio[0]){
                                    podeEnviar = false;
                                }else if(horaAtual[0] == horaEnvio[0]){
                                    if(horaAtual[1] > horaEnvio[1]){
                                        podeEnviar = false;
                                    }else{
                                        podeEnviar = true;
                                    }
                                }else{
                                    podeEnviar = true;
                                }
                            } else{
                                // Se o diário é da data de hoje (e teoricamente já foi publicado)
                                // Essa alteração é para a pessoa poder aceitar uma fatura para o diário que foi publicado hoje (no caso de ter esquecido de clicar no botão "publicar" antes)
                                podeEnviar = true;
                                diarioDeHoje = true;
                            }
                        }
                        $("#divLimite").css('display', 'block');
                        dataLimite = element.diaLimite.split('-');


                        if(podeEnviar){
                            $('#divLabel').css('display', 'none');
                            $('#divBotao').css('display', 'block');

                            if(diarioDeHoje){
                                $("#textoLimite").text('Atenção! Esse diário já foi publicado, portanto tenha cuidado ao aceitar essa fatura. O prazo limite para envio de faturas para esse diário era '+dataLimite[2]+'/'+dataLimite[1]+'/'+dataLimite[0]+' às: '+ horaEnvio[0]+':'+ horaEnvio[1] + ' Horas');
                            } else{
                                $("#textoLimite").text('Para esse diário, você pode enviar até o dia: '+dataLimite[2]+'/'+dataLimite[1]+'/'+dataLimite[0]+' às: '+ horaEnvio[0]+':'+ horaEnvio[1] + ' Horas');
                            }
                        }else{
                            $('#divBotao').css('display', 'none');
                            $('#divLabel').css('display', 'block');
                            $("#textoLimite").text('Para esse diário, você poderia enviar até o dia: '+dataLimite[2]+'/'+dataLimite[1]+'/'+dataLimite[0]+' às: '+ horaEnvio[0]+':'+ horaEnvio[1] + ' Horas');
                            $('#labelText').text('Horário de envio ultrapassado!');
                        }

                    }
                });
             }else{
                $("#divLimite").css('display', 'none');
                $('#divBotao').css('display', 'none');
                $('#divLabel').css('display', 'block');
                $("#textoLimite").text('Escolha um Diário');
             }

         }

    });

</script>

@endauth

@endsection
