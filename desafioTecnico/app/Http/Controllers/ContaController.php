<?php

namespace App\Http\Controllers;
use App\Models\ContaModel;
use App\Models\TransacaoModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ContaController extends Controller
{

     //função que cadastra contas, ela gera o numero de conta baseado no número de acesso da rota, exemplo: se seu acesso é o  100º, seu id será o 100
     public function cadastraConta(){


        $conta = new ContaModel();


        $valores = $this->cadastraMoeda();


        $moedas = 'BRL ';
        $saldo = '0';


        for($i =0; $i<10;$i++){
            $valor = $valores['value'][$i];
            $moedas .= ''.$valor['simbolo'].' ';
            $saldo .= ' 0';
        }


        $conta->moedas = $moedas;
        $conta->saldoMoedas = $saldo;


        if($conta->save()){
            return response()->json(["message"=>"Conta criada com sucesso.O numero da sua conta e: ".$conta->numeroDaConta]);
        }else{
            return response()->json(['message'=>"Ocorreu um erro ao criar a conta!"]);
        }
    }


    public function mostraContas(){

        $contas = ContaModel::all();

        $formataConta = $contas->map(function($conta){
            return ['numeroDaConta'=> $conta->numeroDaConta,
            'created_at'=>$conta->created_at->setTimezone('America/Sao_paulo')->format('d/m/Y H:i:s'),
            'uptaded_at'=>$conta->updated_at->setTimezone('America/Sao_paulo')->format('d/m/Y H:i:s')];
        });

        return response()->json(['contas'=>$formataConta]);
    }

    //função que retorna as moedas existentes, consumindo a API do Banco central disponibilazada
    public function cadastraMoeda()
    {

        $response = Http::get("https://olinda.bcb.gov.br/olinda/servico/PTAX/versao/v1/odata/Moedas?format=json");

        if($response->successful()){
            return $response->json();
        }else{
            return [];
        }
    }


    public function calculaCotacaoVenda($moeda){

        date_default_timezone_set('America/Sao_Paulo');
        // $data = date('m-d-Y',strtotime('-1 days'));
        $data = ('08-16-2021');

        $response = Http::get("https://olinda.bcb.gov.br/olinda/servico/PTAX/versao/v1/odata/CotacaoMoedaDia(moeda=@moeda,dataCotacao=@dataCotacao)?%40moeda=%27{$moeda}%27&%40dataCotacao=%27{$data}%27&%24format=json");

        if($response->successful()){

            $resultado = $response->json();

            if(!empty($resultado['value'])){

                $valores = $resultado['value'][0];
                return ['cotacaoVenda'=>$valores['cotacaoVenda']];

            }else{
                return response()->json(['message'=>'Nenhum valor encontrado na resposta']);
            }
        }else{
            return response()->json(['message'=>'Erro ao obter cotacao da moeda']);
        }
    }

    //função que calcula a cotação de compra baseada na api do banco central
    public function calculaCotacaoCompra($moeda){

        date_default_timezone_set('America/Sao_Paulo');
        // $data = date('m-d-Y',strtotime('-1 days'));
        $data = ('08-16-2021');

         $response = Http::get("https://olinda.bcb.gov.br/olinda/servico/PTAX/versao/v1/odata/CotacaoMoedaDia(moeda=@moeda,dataCotacao=@dataCotacao)?%40moeda=%27{$moeda}%27&%40dataCotacao=%27{$data}%27&%24format=json");

        if($response->successful()){

            $resultado = $response->json();

            if(!empty($resultado['value'])){

            $valores = $resultado['value'][0];
                return ['cotacaoCompra'=>$valores['cotacaoCompra']];

            }else{

            return response()->json(['message'=>'Nenhum valor encontrado na resposta']);
            }
        }else{

            return response()->json(['message'=>'Erro ao obter cotacao da moeda']);
        }

}

    //função que cria a transação
    public function criaTransacao($numeroDaConta,$valor,$moeda,$tipo){


        $transacao = new TransacaoModel();
        $transacao ->conta_numeroDaConta = $numeroDaConta;


        $transacao->tipo = $tipo === 'deposito' ? 'deposito':'saque';

        $transacao->valor = $valor;
        $transacao->moeda = $moeda;

        date_default_timezone_set('America/Sao_Paulo');
        $data = date('Y-m-d');



        if($transacao->save()){
            echo "Transação feita com sucesso!\n";
        }else{
            echo "Transacação realizada com erro!\n";
        }
    }

   //função que realiza o deposito
   public function depositar(Request $request){

        $numeroDaConta = $request->input('numeroDaConta');
        $valor = $request->input('valor');
        $moeda = $request->input('moeda');

        $conta = ContaModel::find($numeroDaConta);

        if($conta){
            if($valor>0){
                $moedas = explode(" ",$conta->moedas);
                $saldo = explode(" ",$conta->saldoMoedas);

                $moedaEncontrada = false;
                foreach($moedas as $i =>$moedaExistente){
                    if($moeda === $moedaExistente){
                        if(isset($saldo[$i])){
                            $saldo[$i] += $valor;
                            $moedaEncontrada = true;
                            break;
                        }
                    }

                }

                if(!$moedaEncontrada){
                    return response()->json(['message'=>'Moeda nao encontrada']);
                }
                $conta->saldoMoedas = implode(" ",$saldo);
                if($conta->save()){
                    $this->criaTransacao($numeroDaConta,$valor,$moeda,'deposito');
                    return response()->json(['message'=>'Depósito realizado com sucesso']);
                }else{
                    return response()->json(['message'=>'Não foi possível realizar o depósito!']);
                }
            }else{
                return response()->json(['message'=>'Valor abaixo de 0, insira um valor maior']);
            }
        }else{
            return response()->json(['message'=>'Essa conta não existe']);
        }
    }



    public function exibirSaldo(Request $request){


        $numeroDaConta = $request->input('numeroDaConta');
        $moeda = $request->input('moeda');


        $conta = ContaModel::find($numeroDaConta);

         if(!$conta){
            return response()->json(['error'=>'Conta não encontrada']);
        }

        $moedas = explode(" ",$conta->moedas);
        $saldos = explode(" ",$conta->saldoMoedas);

        $saldoExibicao = [];

        if ($moeda === NULL){
            foreach($moedas as $i => $moedaAtual){
                if(isset($saldos[$i])){
                    $saldoExibicao[$moedaAtual] = $saldos[$i];
                }

            }
            return response()->json(['numeroDaConta' => $numeroDaConta, 'saldo' => $saldoExibicao]);
        }else{
            foreach($moedas as $i => $moedaExistente){
                if($moeda === $moedaExistente){
                    if(isset($saldos[$i])){
                        if($saldos[$i] >= 0){
                            $saldoMoedaSolicitada = $saldos[$i];
                            $saldoDisponivel = $saldos[$i];
                        }
                    }
                }
            }
           $conversoes = [];
           foreach($moedas as $j => $moedaExistente2){
               if($moedaExistente2 === $moeda){
                   continue;
               }
               if(isset($saldos[$j])){
                   if($saldos[$j] > 0){
                       if($moedaExistente2 !== 'BRL' ){
                           $cotacaoCompra = $this->calculaCotacaoCompra($moedaExistente2);
                           $saldoEmReais = $saldos[$j]*$cotacaoCompra['cotacaoCompra'];
                           $cotacaoVenda = $this->calculaCotacaoVenda($moeda);
                           $saldoNaMoedaSolicitada = $saldoEmReais/$cotacaoVenda['cotacaoVenda'];
                           $saldoDisponivel += $saldoNaMoedaSolicitada;
                       }else {
                           $saldoEmReais = $saldos[$j];
                           $cotacaoVenda = $this->calculaCotacaoVenda($moeda);
                           $saldoNaMoedaSolicitada = $saldoEmReais/$cotacaoVenda['cotacaoVenda'];
                           $saldoDisponivel += $saldoNaMoedaSolicitada;
                       }
                   $conversoes [] = ['moedaDisponivel' => $moedaExistente2,'saldoDisponivel'=>$saldos[$j],'saldoEmBRL' => number_format($saldoEmReais,2), 'saldoDisponivelConvertidoNaMoedaSolicitada'=>number_format($saldoNaMoedaSolicitada,2)];
                   }
               }
           }
       return response()->json(['moedaSolicitada'=>$moeda,'saldoDisponivelDaMoedaSolicitada'=>$saldoMoedaSolicitada,'conversoes'=>$conversoes,'saldoDisponivelTotal'=>number_format($saldoDisponivel,2)]);
        }
    }

    public function sacar(Request $request){


        $numeroDaConta = $request->input('numeroDaConta');
        $valor = $request->input('valor');
        $moeda = $request->input('moeda');


        $conta = ContaModel::find($numeroDaConta);

        if($valor <= 0){
            return response()->json(['error'=>'Insira um valor maior que 0']);
        }

        if(!$conta){
            return response()->json(['error'=>'Conta não encontrada']);
        }

        $moedas = explode(" ",$conta->moedas);
        $saldos = explode(" ",$conta->saldoMoedas);



        foreach($moedas as $i =>$moedaExistente){
            if($moeda === $moedaExistente){
                if(isset($saldos[$i])){
                    if($saldos[$i]>= $valor){
                        $saldos[$i] -= $valor;
                        $conta->saldoMoedas = implode(" ",$saldos);
                        if($conta->save()){
                            $this->criaTransacao($numeroDaConta,$valor,$moeda,'saque');
                            return response()->json(['message'=>'Saque realizado com sucesso']);
                        }else{
                            return response()->json(['message'=>'Não foi possível realizar o saque!']);
                        }
                    }else{


                        $saldoMoedaSolicitada = $saldos[$i];
                        $conversoes = [];
                        $saldoDisponivel = $saldos[$i];
                        foreach($moedas as $j => $moedaExistente2){
                            if($moedaExistente2 === $moeda){
                                continue;
                            }
                            if(isset($saldos[$j])){
                                if($saldos[$j] > 0){
                                    if($moedaExistente2 !== 'BRL' ){
                                        $cotacaoCompra = $this->calculaCotacaoCompra($moedaExistente2);
                                        $saldoEmReais = $saldos[$j]*$cotacaoCompra['cotacaoCompra'];
                                        $cotacaoVenda = $this->calculaCotacaoVenda($moeda);
                                        $saldoNaMoedaSolicitada = $saldoEmReais/$cotacaoVenda['cotacaoVenda'];
                                        $saldoDisponivel += $saldoNaMoedaSolicitada;
                                    }else {
                                        $saldoEmReais = $saldos[$j];
                                        $cotacaoVenda = $this->calculaCotacaoVenda($moeda);
                                        $saldoNaMoedaSolicitada = $saldoEmReais/$cotacaoVenda['cotacaoVenda'];
                                        $saldoDisponivel += $saldoNaMoedaSolicitada;
                                    }
                                $conversoes [] = ['moedaDisponivel' => $moedaExistente2,'saldoDisponivel'=>$saldos[$j],'saldoEmBRL' => number_format($saldoEmReais,2), 'saldoDisponivelConvertidoNaMoedaSolicitada'=>number_format($saldoNaMoedaSolicitada,2)];
                                }
                            }
                        }

                    return response()->json(['moedaSolicitada'=>$moeda,'saldoDisponivelDaMoedaSolicitada'=>$saldoMoedaSolicitada,'conversoes'=>$conversoes,'saldoDisponivelTotal'=>number_format($saldoDisponivel,2)]);

                    }
                }
            }
        }

    }



}


