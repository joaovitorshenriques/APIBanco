<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Models\ContaModel;


class ContaControllerTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */


    //     protected function setUp(): void
    // {
    //     parent::setUp();
    //     // Reiniciar o autoincremento manualmente, se necessário
    //     DB::statement('ALTER TABLE conta AUTO_INCREMENT = 1;');
    // }

    // php artisan test --filter testCadastraConta
    public function testCadastraConta(){

        $response = $this->get('http://127.0.0.1:8000/api/cadastrarconta');

        $response->assertStatus(201);

        $this->assertDatabaseHas('conta',[]);
    }

    // php artisan test --filter testDepositoComSucesso
    public function testDepositoComSucesso(){

        //criando uma conta ficticia
        $conta = ContaModel::create(['numeroDaConta'=>'1','moedas'=>'BRL USD EUR','saldoMoedas'=>'0 0 0']);

        //simulando uma requisição do tipo post
        $response = $this->postJson('api/depositar',['numeroDaConta'=>'1','valor'=> 100,'moeda'=>"BRL"]);

        //verifica se a resposta foi correta
        $response->assertStatus(200);
        $response->assertJson(['message'=>'Depósito realizado com sucesso']);

        //verifica se o saldo foi atualizado corretamente
        $conta = $conta->fresh();
        $this->assertEquals('100 0 0',$conta->saldoMoedas);
    }

    // php artisan test --filter testDepositoEmMoedaInexistente
    public function testDepositoEmMoedaInexistente(){

        //criando uma conta ficticia
        $conta = ContaModel::create(['numeroDaConta'=>'1','moedas'=>'BRL USD EUR','saldoMoedas'=>'0 0 0']);

        //fazendo uma requisição do tipo post que deposita moeda inexistente
        $response = $this->postJson('api/depositar',['numeroDaConta'=>'1','valor'=>100,'moeda'=>'GBP']);

        //verificando a resposta
        $response->assertStatus(400);
        $response->assertJson(['message'=>'Moeda nao encontrada']);
    }

    //php artisan test --filter testSaqueComSucesso
    public function testSaqueComSucesso(){

        //criando uma conta ficticia
        $conta = Contamodel::create(['numeroDaConta'=>'1','moedas'=>'BRL USD EUR','saldoMoedas'=>'100 0 0']);

        //simulando uma requisição do tipo post
        $response = $this->postJson('/api/sacar',['numeroDaConta'=>'1','valor'=>50,'moeda'=>'BRL']);

        //verifica se a resposta foi correta
        $response->assertStatus(200);
        $response->assertJson(['message'=>'Saque realizado com sucesso']);

        //verifica se o saldo foi atualizado corretamente
        $conta = $conta->fresh();
        $this->assertEquals('50 0 0',$conta->saldoMoedas);
    }

    // php artisan test --filter testExibirSaldoTodasMoedas
    public function testExibirSaldoTodasMoedas(){

        //criando uma conta ficticia
        $conta = Contamodel::create(['numeroDaConta'=>'1','moedas'=>'BRL USD EUR','saldoMoedas'=>'100 200 300']);

         //simulando uma requisição do tipo post
        $response = $this->postJson('/api/exibirSaldo',['numeroDaConta'=>'1']);

        //verifica se a resposta foi correta
        $response->assertStatus(200);
        $response->assertJson(['numeroDaConta'=>'1','saldo'=>['BRL'=>100,'USD'=>200,'EUR'=>300]]);

    }

    // php artisan test --filter testExibirSaldoMoedaEspecifica
    public function testExibirSaldoMoedaEspecifica(){

        //criando uma conta ficticia
        $conta = Contamodel::create(['numeroDaConta'=>'1','moedas'=>'BRL USD EUR','saldoMoedas'=>'0 200 0']);

         //simulando uma requisição do tipo post
         $response = $this->postJson('/api/exibirSaldo',['numeroDaConta'=>'1','moeda'=>'USD']);

         //verifica se a resposta foi correta
        $response->assertStatus(200);
        $response->assertJson(['moedaSolicitada'=>'USD','saldoDisponivelDaMoedaSolicitada'=>200,'conversoes'=>[],'saldoDisponivelTotal'=>200]);
    }


}
