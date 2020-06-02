<?php

namespace Alexusmai\LaravelFileManager\Services\TransferService;

use Alexusmai\LaravelFileManager\Traits\PathTrait;
use App\MainDocument;
use Storage;
use Auth;

class LocalTransfer extends Transfer
{
    use PathTrait;

    /**
     * LocalTransfer constructor.
     *
     * @param $disk
     * @param $path
     * @param $clipboard
     */
    public function __construct($disk, $path, $clipboard)
    {
        parent::__construct($disk, $path, $clipboard);
    }

    /**
     * Copy files and folders
     */
    protected function copy($idCu)
    {
        // files
        foreach ($this->clipboard['files'] as $file) {
            Storage::disk($this->disk)->copy(
                $file,
                $this->renamePath($file, $this->path)
            );
        }

        // directories
        $i =0;
        $newPath =[];
        foreach ($this->clipboard['directories'] as $directory) {
            $this->copyDirectory($directory);
            $newPath[$i] = $this->renamePath($directory, $this->path);
            $i++;
        }
        if(count($this->clipboard['directories'])>0){
            $this->insert($newPath,'',$idCu,'copy');
        }
        
    }

    /**
     * Cut files and folders
     */
    protected function cut($idCu)
    {
        $i =0;
        $j = 0;
        $newPath =[];
        $oldPath = [];
        $oldFiles=[];
        // files
        foreach ($this->clipboard['files'] as $file) {
            $oldFile = '/storage/'.$file;
            Storage::disk($this->disk)->move(
                $file,
                $this->renamePath($file, $this->path)
            );
            $newFile =  '/storage/'.$this->renamePath($file, $this->path);
            MainDocument::where('file_path',$oldFile)->update(['id_cu'=>$idCu,'file_path'=>$newFile]);
        }
        
        // directories
        foreach ($this->clipboard['directories'] as $directory) {
            $oldPath[$i] = $directory;
            $files = Storage::disk('storage')->allFiles($oldPath[$i]);
            $j=0;
            foreach($files as $file){
                $oldFiles [$i][$j] = '/storage/'.$file;
                $j++;
            }
            Storage::disk($this->disk)->move(
                $directory,
                $this->renamePath($directory, $this->path)
            );
            $newPath[$i] = $this->renamePath($directory, $this->path);
            $i++;
        }
        if(count($this->clipboard['directories'])>0){
            $this->insert($newPath,$oldFiles,$idCu,'cut');
        }
    }

    //inserting data to database
    public function insert($newPath,$oldFiles,$idCu,$type){
        $i = 0;
        $j = 0;
        $id_user = Auth::user()->id;
        $id_cu = $idCu;
        $newFiles = [];
        foreach($newPath as $path){
            $files = Storage::disk('storage')->allFiles($path);
            $j=0;
            foreach($files as $file){
                $file_path = '/storage/'.$file;
                $info = pathinfo($file);
                $file_name = $info['filename'];
                $file_type = $info['extension'];
                $name = $file_name.'.'.$file_type;
                $input['id_cu'] = $id_cu;
                $input['id_user'] = $id_user;
                $input['file_name'] = $file_name;
                $input['file_type'] = $file_type;
                $input['file_path'] = $file_path;
                $input['name'] = $name;
                if($type=='copy'){
                    MainDocument::create($input);
                }else if($type=='cut'){
                    $newFiles [$i][$j] = $file_path;
                }
                $j++;
            }
            $i++;
        }
        if($type=='cut'){
            for($i=0; $i <count($oldFiles); $i++){
                for($j=0; $j<count($oldFiles[$i]); $j++){
                   MainDocument::where('file_path',$oldFiles[$i][$j])->update(['id_cu'=>$idCu,'file_path'=>$newFiles[$i][$j]]);
                }
            }
        }
    }

    /**
     * Copy directory
     *
     * @param $directory
     */
    protected function copyDirectory($directory)
    {
        // get all directories in this directory
        $allDirectories = Storage::disk($this->disk)
            ->allDirectories($directory);

        $partsForRemove = count(explode('/', $directory)) - 1;

        // create this directories
        foreach ($allDirectories as $dir) {
            Storage::disk($this->disk)->makeDirectory(
                $this->transformPath(
                    $dir,
                    $this->path,
                    $partsForRemove
                )
            );
        }

        // get all files
        $allFiles = Storage::disk($this->disk)->allFiles($directory);

        // copy files
        foreach ($allFiles as $file) {
            Storage::disk($this->disk)->copy(
                $file,
                $this->transformPath($file, $this->path, $partsForRemove)
            );
        }
    }
}
