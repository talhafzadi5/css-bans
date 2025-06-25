<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class ModuleServerSetting extends Model
{
    use HasFactory;

    protected $table = 'module_server_settings';

    protected $fillable = [
        'module_name',
        'name',
        'db_host',
        'db_user',
        'db_pass',
        'db_name',
        'active',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setConnection('mysql');

    }
    public function setDbPassAttribute($value)
    {
        $this->attributes['db_pass'] = Crypt::encryptString($value);
    }

    public function getDbPassAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            // If decryption fails (MAC invalid), return a placeholder
            // This prevents the page from crashing
            return '***ENCRYPTED_DATA_INVALID***';
        }
    }
}
