# Desafio Técnico

## Sobre o desafio 📌

O intuito do desafio é desenvolver uma API RESTful que simule operações bancárias simples (depósito, saque e saldo) para diferentes moedas. A aplicação conta com a utilização das moedas e da taxa cambial (PTAX) de fechamento que é fornecida pela API do Banco Central.

## Especificações 📄

O projeto foi desenvolvido utilizando a linguagem PHP e o framework Laravel, seguindo o padrão MVC. Com o programa XAMPP, foi criada a configuração do servidor web juntamente com o banco de dados MySQL. A construção e utilização da API foram realizadas com o Postman. 

🗂️ Database\Migrations

Trata-se do pacote onde estão inseridas as migrations que criarão as tabelas do banco de dados.



Nos pacotes Controllers, Models e Views encontram-se as principais partes do projeto que seguem o padrão MVC.

🗂️ App\Http\Controllers

Trata-se do pacote que recebe os objetos criados e encapsulados no pacote Models e interage com o banco de dados. Neste pacote foi criada a classe ContaController, nela estão implementadas todas as funções necessárias para o funcionamento correto das operações do projeto.

🗂️ App\Models

Trata-se do pacote que contém todos objetos do sistema. Os dados que são recebidos através da URL são encapsulado nesses objetos. São eles:

* ContaModel -> Representa a conta de uma pessoa, com os seguintes atributos:
    * numeroDaConta -> Designa o número da conta, que é único e incremental;
    * moedas -> Designa as moedas fornecidas pela API do Banco Central e o BRL;
    * saldoMoedas -> Designa o saldo em cada uma das moedas fornecidas.
    * timestamps -> Mostra a hora de criação e atualização da conta.

* TransacaoModel -> Representa as transações realizadas pelo projeto, com os seguintes atributos:
    * id -> Designa o ID da transação, que é único e incremental;
    * conta_NumeroDaConta -> Define a chave estrangeira, que é relacionada com a conta que a transação é realizada;
    * tipo -> Designa o tipo de transação, que pode ser depósito ou saque;
    * moeda -> Designa a moeda em que a transação será realizada;
    * timestamps -> Mostra a hora de criação e atualização da transação.

🗂️ Resources\Views

Neste pacote não foi realizado nenhuma alteração.

## Instalação 🛠️
Após realizar a instalação dos programas, siga os passos abaixo:


1 - Inicialmente, renomeie o arquivo ".env.example" para ".env" e realize as seguintes indicações:
    
  * Crie uma database no phpmyadmin, já que as tabelas foram criadas por meio de migrations para facilitar o versionamento.
    
  * Após a criação da database, é necessário retirar o comentário das funções abaixo:
```
    "DB_HOST=127.0.0.1"
    "DB_PORT=3306"
    "DB_DATABASE="
    "DB_USERNAME="root
    "DB_PASSWORD="
```
  * Retirado os comentários, renomeie as seguintes funções do arquivo:
```
    "APP_NAME="nome_da_sua_aplicação
    "APP_TIMEZONE="America/Sao_Paulo
    "DB_DATABASE="nome_da_sua_database
```
  * Além disso, gere uma "APP_KEY". O código para gerar essa função é:

```
    php artisan key:generate
```
2- Inicie os servidores Apache e MySQL via XAMPP e digite o comando abaixo para criação das tabelas no banco de dados:
```
    php artisan migrate
```
3- Realizando os passos 1 e 2 você já está apto para começar a usar a aplicação via Postman e criar sua primeira conta através da requisição "GET" usando o seguinte link: 
```
    http://127.0.0.1:8000/api/cadastrarconta
```

## Como utilizar as funções 🖥️

Com a conta criada, para utilizar a função de DEPÓSITO basta mudar o tipo de requisição para "POST", acessar a coluna "Body" e digitar os seguintes parâmetros:
```
    {"numeroDaConta": #,
    "valor": # ,
    "moeda": "#"} 
```
substituindo o "#" pelos valores desejados. 

Após isso, insira o link abaixo:

```
    http://127.0.0.1:8000/api/depositar
```

Para o SAQUE, faça o mesmo procedimento e acesse o seguinte link:
```
    http://127.0.0.1:8000/api/sacar
```
OBSERVAÇÃO

Se a conta não possuir saldo suficiente para o saque na moeda solicitada, deverá ser realizada a conversão dos saldos das outras moedas para a moeda solicitada de tal forma que:
* Caso o saldo na conta seja em BRL, converter com a taxa de venda PTAX
para a moeda solicitada no saque;
* Caso contrário, converter o saldo na conta primeiro para BRL a partir da
taxa de compra PTAX, e depois converter o saldo em BRL para a moeda
solicitada no saque a partir da taxa de venda PTAX.

Já a operação de SALDO, através de uma requisição do tipo "POST", acesse a coluna "Body" e digite os seguintes parâmetros:
```
    {"numeroDaConta": '#',
    "moeda": "#"},
```
substituindo o '#' pelos valores desejados.

Após isso, insira o link abaixo:
```
    http://127.0.0.1:8000/api/exibirSaldo
```
OBSERVAÇÃO

A exibição do saldo é realizada de duas formas diferentes:
1. Saldo em cada uma das moedas, caso não seja passado o parâmetro da moeda;
1. Saldo na moeda passado por parâmetro.

Para o saldo de uma determinada moeda passada por parâmetro, a operação deverá retornar o montante total da conta na moeda no qual o saldo está sendo solicitado, sendo necessário converter o valor caso a moeda do saldo na conta seja diferente da moeda solicitada.