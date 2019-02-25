<?php

namespace src\System;

class Config
{
    public static $genesis_key = [
        'genesis_message' => 'Imagine Beyond and Invent Whatever, Wherever - Published by ArtiFriends. Thank you for help - YJ.Lee, JW.Lee, SH.Shin, YS.Han, WJ.Choi, DH.Kang, HG.Lee, KH.Kim, HK.Lee, JS.Han, SM.Park, SJ.Chae, YJ.Jeon, KM.Lee, JH.Kim, mika, ashal, datalater, namedboy, masterguru9, ujuc, johngrib, kimpi, greenmon, HS.Lee, TW.Nam, EH.Park, MJ.Mok',
        'special_thanks' => 'Michelle, Francis, JS.Han, Pang, Jeremy, JG, TY.Lee, SH.Ji, HK.Lim, IS.Choi, CH.Park, SJ.Park, DH.Shin and CK.Park',
        'etc_messages' => [
            [
                'writer' => 'Michelle.Kim',
                'message' => 'I love jjal. ',
            ],
            [
                'writer' => 'Francis.W.Han',
                'message' => 'khan@artifriends.com, I\'m here with JG and SK. ',
            ],
            [
                'writer' => 'JG.Lee',
                'message' => 'In the beginning God created the blocks and the chains. God said, \'Let there be SASEUL\' and saw that it was very good. ',
            ],
            [
                'writer' => 'namedboy',
                'message' => 'This is \'SASEUL\', Welcome to new world.',
            ],
            [
                'writer' => 'ujuc',
                'message' => 'Hello Saseul! :)',
            ]
        ]
    ];

    public static $version = '0.5';
    public static $address_prefix_0 = '0x00';
    public static $address_prefix_1 = '0x6f';

    public static $database_mongodb_host = 'localhost:27017';
    public static $database_mongodb_name_precommit = 'saseul_precommit';
    public static $database_mongodb_name_committed = 'saseul_committed';
    public static $database_mongodb_name_tracker = 'saseul_tracker';

    public static $genesis_coin_value = 1000 * 10000 * 10000 * 10000;
    public static $genesis_deposit_value = 200 * 10000 * 10000 * 10000;
    public static $genesis_address = '0x6f1b0f1ae759165a92d2e7d0b4cae328a1403aa5e35a85';
    public static $genesis_host = 'alice.saseul.net';

    public static $node_private_key = '';
    public static $node_public_key = '';
    public static $node_address = '';
    public static $node_host = '';

    public static $microinterval_chunk = 1 * 1000000;
    public static $fee_rate = 0.00015;
    public static $fee_rate_min = 0.0001;

    public static $directory_apichunks = '/var/saseul-origin/blockchain/apichunks';
    public static $directory_broadcastchunks = '/var/saseul-origin/blockchain/broadcastchunks';
    public static $directory_transactions = '/var/saseul-origin/blockchain/transactions';

    public static $prefix_chunks = 'chunks_';

    public static $testable = true;
}
