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

        //inicializa a conta com saldo zero
        $conta = new ContaModel();

        //chama o método para obter os valores das moedas
        $valores = $this->cadastraMoeda();

        //inicializa as variáveis de moedas e saldo
        $moedas = 'BRL ';
        $saldo = '0';

        //preenchendo as strings de moeda e saldo
        for($i =0; $i<10;$i++){
            $valor = $valores['value'][$i];
            $moedas .= ''.$valor['simbolo'].' ';
            $saldo .= ' 0';
        }

        //definindo valores para os modelos
        $conta->moedas = $moedas;
        $conta->saldoMoedas = $saldo;

        //inserindo a conta no banco de dados e verificando o resultado
        if($conta->save()){
            return response()->json(["message"=>"Conta criada com sucesso.O numero da sua conta e: ".$conta->numeroDaConta],201);
        }else{
            return response()->json(['message'=>"Ocorreu um erro ao criar a conta!"],500);
        }
    }

    //função para mostrar todas contas que estao cadastradas
    public function mostraContas(){
        //obtem todas as contas
        $contas = ContaModel::all();
        //exibe todas as contas
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
        //consumindo a api e jogando em uma variavel
        $response = Http::get("https://olinda.bcb.gov.br/olinda/servico/PTAX/versao/v1/odata/Moedas?format=json");
        //utilizamos http get pois estamos pegando algo da api
        //verificando se o pedido foi bem sucedido
        if($response->successful()){ //usamos ok() pois é um metodo integrado do laravel
            return $response->json();
        }else{
            return []; //retorna o conjunto vazio
        }
    }

    //função que calcula a cotação de venda baseada na api do Banco central
    public function calculaCotacaoVenda($moeda){

        date_default_timezone_set('America/Sao_Paulo'); // Configura o fuso horário para São Paulo
        // $data = date('m-d-Y',strtotime('-1 days'));
        $data = ('08-16-2021');
        //consumindo a api do banco central
        $response = Http::get("https://olinda.bcb.gov.br/olinda/servico/PTAX/versao/v1/odata/CotacaoMoedaDia(moeda=@moeda,dataCotacao=@dataCotacao)?%40moeda=%27{$moeda}%27&%40dataCotacao=%27{$data}%27&%24format=json");
        //verifica se a requisição foi bem-sucedida
        if($response->successful()){
            // decodifica a resposta JSON em um array
            $resultado = $response->json();

            if(!empty($resultado['value'])){
                //acessa o primeiro elemento do array value
                $valores = $resultado['value'][0];
                return ['cotacaoVenda'=>$valores['cotacaoVenda']];

            }else{
                return response()->json(['message'=>'Nenhum valor encontrado na resposta'],500);
            }
        }else{
            return response()->json(['message'=>'Erro ao obter cotacao da moeda'],500);
        }
    }

    //função que calcula a cotação de compra baseada na api do banco central
    public function calculaCotacaoCompra($moeda){

        date_default_timezone_set('America/Sao_Paulo'); // Configura o fuso horário para São Paulo
        // $data = date('m-d-Y',strtotime('-1 days'));
        $data = ('08-16-2021');
        //consumindo a api do banco central
         $response = Http::get("https://olinda.bcb.gov.br/olinda/servico/PTAX/versao/v1/odata/CotacaoMoedaDia(moeda=@moeda,dataCotacao=@dataCotacao)?%40moeda=%27{$moeda}%27&%40dataCotacao=%27{$data}%27&%24format=json");
        //verifica se a requisição foi bem-sucedida
        if($response->successful()){
            // decodifica a resposta JSON em um array
            $resultado = $response->json();

            if(!empty($resultado['value'])){
            //acessa o primeiro elemento do array value
            $valores = $resultado['value'][0];
                return ['cotacaoCompra'=>$valores['cotacaoCompra']];

            }else{

            return response()->json(['message'=>'Nenhum valor encontrado na resposta'],500);
            }
        }else{
           // Log::error('Erro ao obter cotacao da moeda: ',['status'=>$response->status()]);
            return response()->json(['message'=>'Erro ao obter cotacao da moeda'],500);
        }

}

    //função que cria a transação
    public function criaTransacao($numeroDaConta,$valor,$moeda,$tipo){

        //criando um objeto transação
        $transacao = new TransacaoModel();
        $transacao ->conta_numeroDaConta = $numeroDaConta;

        //atribuindo o tipo de transacao para deposito ou saque
        $transacao->tipo = $tipo === 'deposito' ? 'deposito':'saque';

        $transacao->valor = $valor; //atribuindo a variavel valor ao campo valor da transacao
        $transacao->moeda = $moeda; //atribuindo a variavel moeda ao campo moeda da transacao

        date_default_timezone_set('America/Sao_Paulo'); // definindo o fuso horario
        $data = date('Y-m-d');


        //verificando se a transação foi realizada ou não
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
        //verificando se a conta existe no banco de dados
        $conta = ContaModel::find($numeroDaConta);

        if($conta){ //verifica se a conta foi encontrada
            if($valor>0){ //verifica se o valor a ser adicionado é maior que 0
                $moedas = explode(" ",$conta->moedas); //converte a string moedas em um array
                $saldo = explode(" ",$conta->saldoMoedas); //converte a string saldo em um array

                //variavel pra controlar se a moeda foi encontrada
                $moedaEncontrada = false;
                //loop para passar por todas as moedas da conta
                foreach($moedas as $i =>$moedaExistente){
                    if($moeda === $moedaExistente){ //verifica se a moeda do parametro é igual a alguma moeda que tem na conta
                        if(isset($saldo[$i])){ //necessario para verificar se o indice existe
                            $saldo[$i] += $valor; //altera o saldo na determinada moeda selecionada acima
                            $moedaEncontrada = true; //a moeda escolhida foi encontrada
                            break; //encerra o código quando encontrar a moeda escolhida
                        }
                    }

                }
                //verifica se a moeda nao foi encontrada
                if(!$moedaEncontrada){
                    return response()->json(['message'=>'Moeda nao encontrada'],400);
                }
                $conta->saldoMoedas = implode(" ",$saldo); //transformando saldoMoedas em string novamente
                //realizando o depósito
                if($conta->save()){
                    $this->criaTransacao($numeroDaConta,$valor,$moeda,'deposito');
                    return response()->json(['message'=>'Depósito realizado com sucesso']);
                }else{
                    return response()->json(['message'=>'Não foi possível realizar o depósito!'],500);
                }
            }else{
                return response()->json(['message'=>'Valor abaixo de 0, insira um valor maior'],400);
            }
        }else{
            return response()->json(['message'=>'Essa conta não existe'],400);
        }
    }



    public function exibirSaldo(Request $request){

        //solicitando as seguintes variaveis
        $numeroDaConta = $request->input('numeroDaConta');
        $moeda = $request->input('moeda');

        //verificando se a conta existe no banco de dados
        $conta = ContaModel::find($numeroDaConta);

         //passando mensagem caso a conta não esteja no banco de dados
         if(!$conta){
            return response()->json(['error'=>'Conta não encontrada']);
        }

        $moedas = explode(" ",$conta->moedas); //transforma moedas em um array
        $saldos = explode(" ",$conta->saldoMoedas); //transforma saldos em um array

        $saldoExibicao = []; //criando a variavel de exibição do saldo

        if ($moeda === NULL){ //se o parametro não é passado
            foreach($moedas as $i => $moedaAtual){ // array que percorre todas moedas
                //verificando se existe o indice antes de entrar
                if(isset($saldos[$i])){ //verifica se o indice existe
                    $saldoExibicao[$moedaAtual] = $saldos[$i];
                }

            }
            return response()->json(['numeroDaConta' => $numeroDaConta, 'saldo' => $saldoExibicao]);
        }else{
            //loop para passar por todas as moedas e definir o saldo da moeda solicitada
            foreach($moedas as $i => $moedaExistente){
                if($moeda === $moedaExistente){
                    if(isset($saldos[$i])){ // verifica se o indice existe, condição adicionado pois estava dando um erro
                        if($saldos[$i] >= 0){
                            $saldoMoedaSolicitada = $saldos[$i];
                            $saldoDisponivel = $saldos[$i]; //variavel controle para imprimir o saldo disponivel na moeda solicitada
                        }
                    }
                }
            }
           //variavel criada apenas pra impressão
           $conversoes = []; //array para imprimir as conversoes das moedas disponiveis
           foreach($moedas as $j => $moedaExistente2){ //outro loop para passar por cada moeda
               if($moedaExistente2 === $moeda){ //condição criada para a moeda solicitada não ser integrada no array conversoes
                   continue;
               }
               if(isset($saldos[$j])){ // verifica se o indice existe, condição adicionado pois estava dando um erro
                   if($saldos[$j] > 0){ //verifica se o saldo da moeda é maior que 0
                       if($moedaExistente2 !== 'BRL' ){ //verifica se a moeda é diferente do real para fazer a conversão
                           $cotacaoCompra = $this->calculaCotacaoCompra($moedaExistente2); //calcula a cotacao de compra da moeda
                           $saldoEmReais = $saldos[$j]*$cotacaoCompra['cotacaoCompra']; //faz a conversão do saldo da moeda para BRL
                           $cotacaoVenda = $this->calculaCotacaoVenda($moeda); //calcula a cotacao de venda ad moeda
                           $saldoNaMoedaSolicitada = $saldoEmReais/$cotacaoVenda['cotacaoVenda']; // faz a conversao do BRL para moeda solicitada
                           $saldoDisponivel += $saldoNaMoedaSolicitada; // adiciona esse saldo na variavel controle
                       }else { //se a moeda for real so adiciona o saldo em real para a variavel de controle
                           $saldoEmReais = $saldos[$j]; // variavel controle do saldo em BRL
                           $cotacaoVenda = $this->calculaCotacaoVenda($moeda); //calcula a cotacao de venda da moeda solicitada
                           $saldoNaMoedaSolicitada = $saldoEmReais/$cotacaoVenda['cotacaoVenda']; // faz a conversao do BRL para moeda solicitada
                           $saldoDisponivel += $saldoNaMoedaSolicitada; // adiciona esse saldo na variavel controle
                       }
                       //adicionando as variaveis ao array conversoes que é representado na resposta JSON
                   $conversoes [] = ['moedaDisponivel' => $moedaExistente2,'saldoDisponivel'=>$saldos[$j],'saldoEmBRL' => number_format($saldoEmReais,2), 'saldoDisponivelConvertidoNaMoedaSolicitada'=>number_format($saldoNaMoedaSolicitada,2)];
                   }
               }
           }
       return response()->json(['moedaSolicitada'=>$moeda,'saldoDisponivelDaMoedaSolicitada'=>$saldoMoedaSolicitada,'conversoes'=>$conversoes,'saldoDisponivelTotal'=>number_format($saldoDisponivel,2)]);
        }
    }

    public function sacar(Request $request){

        //requisitando as variaveis parametros
        $numeroDaConta = $request->input('numeroDaConta');
        $valor = $request->input('valor');
        $moeda = $request->input('moeda');

        //verificando se a conta está no banco de dados
        $conta = ContaModel::find($numeroDaConta);

        //passando mensagem caso a conta não esteja no banco de dados
        if(!$conta){
            return response()->json(['error'=>'Conta não encontrada']);
        }

        $moedas = explode(" ",$conta->moedas); //transforma moedas em um array
        $saldos = explode(" ",$conta->saldoMoedas); //transforma saldos em um array


        //loop que passa por todas moedas da contas até achar a solicitada
        foreach($moedas as $i =>$moedaExistente){
            if($moeda === $moedaExistente){ //verifica se a moeda do parametro é igual a alguma moeda que tem na conta
                if(isset($saldos[$i])){ // verifica se o indice existe, condição adicionado pois estava dando um erro
                    if($saldos[$i]>= $valor){ //verifica se há saldo suficiente na determinada moeda
                        $saldos[$i] -= $valor; //realiza o saque
                        $conta->saldoMoedas = implode(" ",$saldos); //atualiza o saldo no banco de dados
                        if($conta->save()){
                            $this->criaTransacao($numeroDaConta,$valor,$moeda,'saque');
                            return response()->json(['message'=>'Saque realizado com sucesso']);
                        }else{
                            return response()->json(['message'=>'Não foi possível realizar o saque!'],500);
                        }
                    }else{ //saldo insuficiente na moeda solicitada

                        //variavel criada apenas pra impressão
                        $saldoMoedaSolicitada = $saldos[$i];
                        $conversoes = []; //array para imprimir as conversoes das moedas disponiveis
                        $saldoDisponivel = $saldos[$i]; //variavel controle para imprimir o saldo disponivel na moeda solicitada
                        foreach($moedas as $j => $moedaExistente2){ //outro loop para passar por cada moeda
                            if($moedaExistente2 === $moeda){ //condição criada para a moeda solicitada não ser integrada no array conversoes
                                continue;
                            }
                            if(isset($saldos[$j])){ // verifica se o indice existe, condição adicionado pois estava dando um erro
                                if($saldos[$j] > 0){ //verifica se o saldo da moeda é maior que 0
                                    if($moedaExistente2 !== 'BRL' ){ //verifica se a moeda é diferente do real para fazer a conversão
                                        $cotacaoCompra = $this->calculaCotacaoCompra($moedaExistente2); //calcula a cotacao de compra da moeda
                                        $saldoEmReais = $saldos[$j]*$cotacaoCompra['cotacaoCompra']; //faz a conversão do saldo da moeda para BRL
                                        $cotacaoVenda = $this->calculaCotacaoVenda($moeda); //calcula a cotacao de venda ad moeda
                                        $saldoNaMoedaSolicitada = $saldoEmReais/$cotacaoVenda['cotacaoVenda']; // faz a conversao do BRL para moeda solicitada
                                        $saldoDisponivel += $saldoNaMoedaSolicitada; // adiciona esse saldo na variavel controle
                                    }else { //se a moeda for real so adiciona o saldo em real para a variavel de controle
                                        $saldoEmReais = $saldos[$j]; // variavel controle do saldo em BRL
                                        $cotacaoVenda = $this->calculaCotacaoVenda($moeda); //calcula a cotacao de venda da moeda solicitada
                                        $saldoNaMoedaSolicitada = $saldoEmReais/$cotacaoVenda['cotacaoVenda']; // faz a conversao do BRL para moeda solicitada
                                        $saldoDisponivel += $saldoNaMoedaSolicitada; // adiciona esse saldo na variavel controle
                                    }
                                    //adicionando as variaveis ao array conversoes que é representado na resposta JSON
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










    public function testCadastraMoeda(){
        $moedas = $this->cadastraMoeda();
            if(!empty($moedas['value'])){
                foreach($moedas['value']as $moeda){
                    echo "Moeda: ".$moeda['simbolo']. "-".$moeda['nomeFormatado']."\n";
                }
            }else{
                echo "Nenhuma moeda encontrada.\n";
            }
    }

    public function testCalculaCotacaoVenda($moeda){
        $cotacao = $this->calculaCotacaoVenda($moeda);
            return response()->json(['cotacaoVenda'=>$cotacao]);
    }

    public function testCalculaCotacaoCompra($moeda){
        $cotacao = $this->calculaCotacaoCompra($moeda);
            return response()->json(['cotacaoCompra'=>$cotacao]);
    }

}







// public function sacar(Request $request){

//     //requisitando as variaveis parametros
//     $numeroDaConta = $request->input('numeroDaConta');
//     $valor = $request->input('valor');
//     $moeda = $request->input('moeda');

//     //verificando se a conta está no banco de dados
//     $conta = ContaModel::find($numeroDaConta);

//     //passando mensagem caso a conta não esteja no banco de dados
//     if(!$conta){
//         return response()->json(['error'=>'Conta não encontrada']);
//     }

//     $moedas = explode(" ",$conta->moedas); //transforma moedas em um array
//     $saldos = explode(" ",$conta->saldoMoedas); //transforma saldos em um array


//     //loop que passa por todas moedas da contas até achar a solicitada
//     foreach($moedas as $i =>$moedaExistente){
//         if($moeda === $moedaExistente){ //verifica se a moeda do parametro é igual a alguma moeda que tem na conta
//             if(isset($saldos[$i])){ // verifica se o indice existe, condição adicionado pois estava dando um erro
//                 if($saldos[$i]>= $valor){ //verifica se há saldo suficiente na determinada moeda
//                     $saldos[$i] -= $valor; //realiza o saque
//                     $conta->saldoMoedas = implode(" ",$saldos); //atualiza o saldo no banco de dados
//                     $conta->save();
//                     return response()->json(['success'=>'Saque realizado com sucesso']);
//                 }else{ //saldo insuficiente na moeda solicitada

//                     //variavel criada apenas pra impressão
//                     $saldoMoedaSolicitada = $saldos[$i];
//                     $conversoes = []; //array para imprimir as conversoes das moedas disponiveis
//                     $saldoDisponivel = $saldos[$i]; //variavel controle para imprimir o saldo disponivel na moeda solicitada
//                     foreach($moedas as $j => $moedaExistente2){ //outro loop para passar por cada moeda
//                         if($moedaExistente2 === $moeda){ //condição criada para a moeda solicitada não ser integrada no array conversoes
//                             continue;
//                         }
//                         if(isset($saldos[$j])){ // verifica se o indice existe, condição adicionado pois estava dando um erro
//                             if($saldos[$j] > 0){ //verifica se o saldo da moeda é maior que 0
//                                 if($moedaExistente2 !== 'BRL' ){ //verifica se a moeda é diferente do real para fazer a conversão
//                                     $cotacaoCompra = $this->calculaCotacaoCompra($moedaExistente2); //calcula a cotacao de compra da moeda
//                                     $saldoEmReais = $saldos[$j]*$cotacaoCompra['cotacaoCompra']; //faz a conversão do saldo da moeda para BRL
//                                     $cotacaoVenda = $this->calculaCotacaoVenda($moeda); //calcula a cotacao de venda ad moeda
//                                     $saldoNaMoedaSolicitada = $saldoEmReais/$cotacaoVenda['cotacaoVenda']; // faz a conversao do BRL para moeda solicitada
//                                     $saldoDisponivel += $saldoNaMoedaSolicitada; // adiciona esse saldo na variavel controle
//                                 }else { //se a moeda for real so adiciona o saldo em real para a variavel de controle
//                                     $saldoEmReais = $saldos[$j]; // variavel controle do saldo em BRL
//                                     $cotacaoVenda = $this->calculaCotacaoVenda($moeda); //calcula a cotacao de venda da moeda solicitada
//                                     $saldoNaMoedaSolicitada = $saldoEmReais/$cotacaoVenda['cotacaoVenda']; // faz a conversao do BRL para moeda solicitada
//                                     $saldoDisponivel += $saldoNaMoedaSolicitada; // adiciona esse saldo na variavel controle
//                                 }
//                                 //adicionando as variaveis ao array conversoes que é representado na resposta JSON
//                             $conversoes [] = ['moedaDisponivel' => $moedaExistente2,'saldoDisponivel'=>$saldos[$j],'saldoEmBRL' => number_format($saldoEmReais,2), 'saldoDisponivelConvertidoNaMoedaSolicitada'=>number_format($saldoNaMoedaSolicitada,2)];
//                             }
//                         }
//                     }

//                 return response()->json(['moedaSolicitada'=>$moeda,'saldoDisponivelDaMoedaSolicitada'=>$saldoMoedaSolicitada,'conversoes'=>$conversoes,'saldoDisponivelTotal'=>number_format($saldoDisponivel,2)]);

//                 }
//             }
//         }
//     }

// }
