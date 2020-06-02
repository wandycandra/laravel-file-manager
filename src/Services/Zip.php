<?php

namespace Alexusmai\LaravelFileManager\Services;

use Illuminate\Http\Request;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use ZipArchive;
use Storage;
use App\MainDocument;
use Auth;
use App\Cu;

class Zip
{
    protected $zip;
    protected $request;
    protected $pathPrefix;
    public    $idCu;

    /**
     * Zip constructor.
     *
     * @param ZipArchive $zip
     * @param Request    $request
     */
    public function __construct(ZipArchive $zip, Request $request)
    {
        $this->zip = $zip;
        $this->request = $request;
        $this->idCu = Auth::user()->getIdCu();
        $this->pathPrefix = Storage::disk($request->input('disk'))
            ->getDriver()
            ->getAdapter()
            ->getPathPrefix();
    }

    /**
     * Create new zip archive
     *
     * @return array
     */
    public function create($mainDir)
    {
        if ($this->createArchive($mainDir)) {
            return [
                'result' => [
                    'status'  => 'success',
                    'message' => null,
                ],
            ];
        }

        return [
            'result' => [
                'status'  => 'warning',
                'message' => 'zipError',
            ],
        ];
    }

    /**
     * Extract
     *
     * @return array
     */
    public function extract($mainDir)
    {
        if ($this->extractArchive($mainDir)) {
            return [
                'result' => [
                    'status'  => 'success',
                    'message' => null,
                ],
            ];
        }

        return [
            'result' => [
                'status'  => 'warning',
                'message' => 'zipError',
            ],
        ];
    }

    /**
     * Create zip archive
     *
     * @return bool
     */
    protected function createArchive($mainDir)
    {
        // elements list
        $elements = $this->request->input('elements');
        // create or overwrite archive
        if($this->idCu==0 && $mainDir!=null && $mainDir!='BKCU'){
            $id = Cu::where('name', $mainDir)->get();
            $this->idCu = $id[0]->id;
        }
        $id_user = Auth::user()->id;
        $id_cu = $this->idCu;
        $file_path = '/storage/'.$this->request->input('path').'/'.$this->request->input('name');
        $info = pathinfo($file_path);
        $file_name = $info['filename'];
        $name = $file_name.'.zip';
        $file_type = 'zip';
        $input['id_cu'] = $id_cu;
        $input['id_user'] = $id_user;
        $input['file_name'] = $file_name;
        $input['file_type'] = $file_type;
        $input['file_path'] = $file_path;
        $input['name'] = $name;
        if ($this->zip->open(
                $this->createName(),
                ZIPARCHIVE::OVERWRITE | ZIPARCHIVE::CREATE
            ) === true
        ) {
            // files processing
            if ($elements['files']) {
                foreach ($elements['files'] as $file) {
                    $this->zip->addFile(
                        $this->pathPrefix.$file,
                        basename($file)
                    );
                }
            }

            // directories processing
            if ($elements['directories']) {
                $this->addDirs($elements['directories']);
            }

            $this->zip->close();
            MainDocument::create($input);
            return true;
        }
        return false;
    }

    /**
     * Archive extract
     *
     * @return bool
     */
    protected function extractArchive($mainDir)
    {
        $zipPath = $this->pathPrefix.$this->request->input('path');

        $rootPath = dirname($zipPath);
        // extract to new folder
        if($this->idCu==0 && $mainDir!=null && $mainDir!='BKCU'){
            $id = Cu::where('name', $mainDir)->get();
            $this->idCu = $id[0]->id;
        }
        $folder = $this->request->input('folder');
        $file_path='';
        $id_user = Auth::user()->id;
        $id_cu = $this->idCu;

        if ($this->zip->open($zipPath) === true) {
            $this->zip->extractTo($folder ? $rootPath.'/'.$folder : $rootPath);
                for ($i = 0; $i < $this->zip->numFiles; $i++) {
                    $filename = $this->zip->getNameIndex($i);
                    if($folder==null){
                        $file_path = '/storage/'.str_replace('\\','/',dirname($this->request->input('path')).'/'.$filename);
                    }else{
                        $file_path = '/storage/'.str_replace('\\','/',dirname($this->request->input('path')).'/'.$folder.'/'.$filename);
                    }
                    $info = pathinfo($file_path);
                    $file_name = $info['filename'];
                    $file_type = $info['extension'];
                    $name = $file_name.'.'.$file_type;
                    $input['id_cu'] = $id_cu;
                    $input['id_user'] = $id_user;
                    $input['file_name'] = $file_name;
                    $input['file_type'] = $file_type;
                    $input['file_path'] = $file_path;
                    $input['name'] = $name;
                    $isExist = MainDocument::where('file_path',$file_path)->get();
                    if(count($isExist)<=0){
                        MainDocument::create($input);
                    }
                }
            $this->zip->close();
            return true;
        }

        return false;
    }

    /**
     * Add directories - recursive
     *
     * @param array $directories
     */
    protected function addDirs(array $directories)
    {
        foreach ($directories as $directory) {

            // Create recursive directory iterator
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->pathPrefix.$directory),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $name => $file) {
                // Get real and relative path for current item
                $filePath = $file->getRealPath();
                $relativePath = substr(
                    $filePath,
                    strlen($this->fullPath($this->request->input('path')))
                );

                if (!$file->isDir()) {
                    // Add current file to archive
                    $this->zip->addFile($filePath, $relativePath);
                } else {
                    // add empty folders
                    if (!glob($filePath.'/*')) {
                        $this->zip->addEmptyDir($relativePath);
                    }
                }
            }
        }
    }

    /**
     * Create archive name with full path
     *
     * @return string
     */
    protected function createName()
    {
        return $this->fullPath($this->request->input('path'))
            .$this->request->input('name');
    }

    /**
     * Generate full path
     *
     * @param $path
     *
     * @return string
     */
    protected function fullPath($path)
    {
        return $path ? $this->pathPrefix.$path.'/' : $this->pathPrefix;
    }
}
