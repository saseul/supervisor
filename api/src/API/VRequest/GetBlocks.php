<?php

namespace src\API\VRequest;

use src\API;
use src\System\Block;

class GetBlocks extends API
{
    public function _process()
    {
        $this->data['committed'] = Block::GetLastBlocks(100);
    }
}
