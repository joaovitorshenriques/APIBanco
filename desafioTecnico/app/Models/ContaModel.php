<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContaModel extends Model
{
    use HasFactory;

    //associando a tabela ao modelo
    protected $table = 'conta';

    //associando a chave primária a tabela
    protected $primaryKey = 'numeroDaConta';

    //atributos que podem ser atribuidos em massa
    protected $fillable = ['saldoTotalReais','moedas','saldoMoedas'];

    //convertendo o atributo do saldo para float
    protected $casts = ['saldoTotalReais' => 'float',];

    //o tipo da primary key
    protected $keyType = 'int';

    //para mostrar data e hora
    public $timestamps = true;
}
