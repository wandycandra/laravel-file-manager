<?php

namespace Alexusmai\LaravelFileManager\Services\ACLService;
use Alexusmai\LaravelFileManager\Services\ACLService\ACLRepository;
use Auth;
use App\Cu;
use Illuminate\Support\Facades\Storage;

/**
 * Class ConfigACLRepository
 *
 * Get rules from file-manager config file - aclRules
 *
 * @package Alexusmai\LaravelFileManager\Services\ACLService
 */
class ConfigACLRepository implements ACLRepository
{
    /**
     * Get user ID
     *
     * @return mixed
     */
    public function getUserID()
    {
        return Auth::user()->getIdCu();
    }

    /**
     * Get ACL rules list for user
     *
     * @return array
     */
    public function getRules(): array
    {
        $id = Auth::user()->getIdCu();
        $cu = 'BKCU';
        $user = Auth::user();
        if($id!=0){
            $cu = CU::findOrFail($id)->name;
        }

        if(!Storage::disk('storage')->exists($cu)) {
            Storage::disk('storage')->makeDirectory($cu, 0775, true); //creates directory
        }

        if($id!=0 ){
            return [
                ['disk' => 'storage', 'path' => '/*', 'access' => 1],
                ['disk' => 'storage', 'path' => $cu.'*', 'access' => 2],
                // ['disk' => 'storage', 'path' => $cu, 'access' => 1]
            ]; 
        }else if(!$user->can['index_disk_cu']){
            return [
                ['disk' => 'storage', 'path' => '/*', 'access' => 1],
                ['disk' => 'storage', 'path' => $cu.'*', 'access' => 2],
                // ['disk' => 'storage', 'path' => $cu, 'access' => 1]
            ]; 
        }else{
            return [
                ['disk' => 'storage', 'path' => '*', 'access' => 2]
            ]; 
        }
               
        
        // return [
        //     ['disk' => 'disk-name', 'path' => '/', 'access' => 1],                                  // main folder - read
        //     ['disk' => 'disk-name', 'path' => 'users', 'access' => 1],                              // only read
        //     ['disk' => 'disk-name', 'path' => 'users/'. \Auth::user()->name, 'access' => 1],        // only read
        //     ['disk' => 'disk-name', 'path' => 'users/'. \Auth::user()->name .'/*', 'access' => 2],  // read and write
        // ];
    }
}
