<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Food extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name','description','ingredients','price','rate','types',
        'picturePath'
    ];

    // UNTUK MENGAMBIL DATA TANGGAL  MENGGUNAKAN EPOCH
    // ATAU MENGAMBIL DATA TANGGAL YANG UNIK

    public function getCreatedAtAttribute($value) 
    {
        return Carbon::parse($value)->timestamp ;
    }
    
    public function getUpdatedAtAttribute($value) 
    {
        return Carbon::parse($value)->timestamp;
    }

    // UNTUK MENGATASI ('picturePath') JIKA DI LARAVEL
    // NAMA TERSEBUT TIDAK TERDETEK, HARUS MENGGUNAKAN '_'
    // MAKA DARI ITU FUNGSI DIBAWAH INI UNTUK MENGATASI KARENA
    // DI FLUTTER MENGGUNAKAN NAMA FUNGSINYA 'picturePath'

    public function toArray()
    {
        $toArray = parent::toArray();
        $toArray['picturePath'] = $this->picturePath;
        return $toArray;
    }

    public function getPicturePathAttribute() 
    {
        return url('') . Storage::url($this->attributes['picturePath']);
    }

}
