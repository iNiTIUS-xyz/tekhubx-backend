<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

class UniqueIdentifierService
{
    public static function generateUniqueIdentifier(Model $model, $columnName = 'id', $type = 'default' ,$length = 8)
    {
        do {
            if($type == 'uuid')
            {
                $proposedIdentifier = static::generateUuidMd5();
            }else{
                $proposedIdentifier = static::generateRandomIdentifier($length);
            }
        } while (static::identifierExists($model, $columnName, $proposedIdentifier));

        return $proposedIdentifier;
    }

    private static function generateUuidMd5()
    {
        $uuid = uuid_create(UUID_TYPE_RANDOM);
        $md5Hash = md5($uuid);

        return $md5Hash;
    }
   private static function generateRandomIdentifier($length)
    {
        //for length 8
        return mt_rand(10000000, 99999999);
    }

    private static function identifierExists(Model $model, $columnName, $identifier)
    {
        return $model->where($columnName, $identifier)->exists();
    }
}
