<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransacaoModel extends Model
{
    use HasFactory;

    //associando a tabela ao modelo
    protected $table = 'transacao';

    //associando a chave primaria a tabela
    protected $primaryKey = 'id';

    //os atributos que podem ser preenchidos em massa
    protected $fillable = ['conta_numeroDaConta','tipo','valor','moeda','data'];

    //o tipo da chave primaria
    protected $keyType = 'int';

    //conversao de atributos
    protected $casts = ['valor'=>'float','data'=>'date'];
}
