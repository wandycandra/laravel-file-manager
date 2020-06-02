<?php

namespace Alexusmai\LaravelFileManager\Services\TransferService;

abstract class Transfer
{
    public $disk;
    public $path;
    public $clipboard;

    /**
     * Transfer constructor.
     *
     * @param $disk
     * @param $path
     * @param $clipboard
     */
    public function __construct($disk, $path, $clipboard)
    {
        $this->disk = $disk;
        $this->path = $path;
        $this->clipboard = $clipboard;
    }

    /**
     * Transfer files and folders
     *
     * @return array
     */
    public function filesTransfer($idCu)
    {
        try {
            // determine the type of operation
            if ($this->clipboard['type'] === 'copy') {
                $this->copy($idCu);
            } elseif ($this->clipboard['type'] === 'cut') {
                $this->cut($idCu);
            }
        } catch (\Exception $exception) {
            return [
                'result' => [
                    'status'  => 'error',
                    'message' => $exception->getMessage(),
                ],
            ];
        }

        return [
            'result' => [
                'status'  => 'success',
                'message' => 'copied',
            ],
        ];
    }

    abstract protected function copy($idCu);

    abstract protected function cut($idCu);
}
