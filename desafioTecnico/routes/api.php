<?php


use App\Http\Controllers\ContaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('api')->group(function () {
    Route::get('/cadastrarconta',[ContaController::class,'cadastraConta']);
    Route::get('/contas',[ContaController::class,'mostraContas']);
    Route::get('/achar-conta/{numeroDaConta}/{numContas}',[ContaController::class,'achaConta']);
    Route::get('/test/cadastraMoeda',[ContaController::class,'testCadastraMoeda']);
    Route::post('/depositar',[ContaController::class,'depositar']);
    Route::post('/transacao/{numeroDaConta}/{valor}/{moeda}/{tipo}', [ContaController::class, 'criaTransacao']);
    Route::post('/exibirSaldo',[ContaController::class,'exibirSaldo']);
    Route::get('/test/calculaCotacaoCompra/{moeda}',[ContaController::class,'testCalculaCotacaoCompra']);
    Route::get('/test/calculaCotacaoVenda/{moeda}',[ContaController::class,'testCalculaCotacaoVenda']);
    Route::post('/sacar',[ContaController::class,'sacar']);
});
