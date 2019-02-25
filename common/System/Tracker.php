<?php

namespace src\System;

use src\Util\Parser;

class Tracker
{
    private static $namespace_tracker = 'saseul_tracker.tracker';
    private static $db_tracker = 'saseul_tracker';
    private static $collection_tracker = 'tracker';

    public static function GetNode($query)
    {
        $db = Database::GetInstance();
        $rs = $db->Query(self::$namespace_tracker, $query);
        $nodes = [];

        foreach ($rs as $item) {
            $node = [
                'host' => '',
                'address' => '',
                'rank' => 'light',
                'status' => 'none',
                'my_observed_status' => 'none',
            ];

            if (isset($item->host)) {
                $node['host'] = $item->host;
            }

            if (isset($item->address)) {
                $node['address'] = $item->address;
            }

            if (isset($item->rank)) {
                $node['rank'] = $item->rank;
            }

            if (isset($item->status)) {
                $node['status'] = $item->status;
            }

            if (isset($item->my_observed_status)) {
                $node['my_observed_status'] = $item->my_observed_status;
            }

            $nodes[] = $node;
        }

        return $nodes;
    }

    public static function GetNodeAddress($query)
    {
        $db = Database::GetInstance();
        $rs = $db->Query(self::$namespace_tracker, $query);
        $nodes = [];

        foreach ($rs as $item) {
            $node = Parser::obj2array($item);
            unset($node['_id']);
            $nodes[] = $node['address'];
        }

        return $nodes;
    }

    public static function IsNode($address, $query)
    {
        $db = Database::GetInstance();
        $query = array_merge(['address' => $address], $query);
        $command = [
            'count' => self::$collection_tracker,
            'query' => $query,
        ];

        $rs = $db->Command(self::$db_tracker, $command);
        $count = 0;

        foreach ($rs as $item) {
            $count = $item->n;

            break;
        }

        if ($count > 0) {
            return true;
        }

        return false;
    }

    public static function GetFullNode()
    {
        $query = [
            'rank' => [
                '$in' => [
                    'validator',
                    'supervisor',
                    'arbiter',
                ]
            ]
        ];

        return self::GetNode($query);
    }

    public static function GetValidator()
    {
        return self::GetNode(['rank' => 'validator']);
    }

    public static function GetSupervisor()
    {
        return self::GetNode(['rank' => 'supervisor']);
    }

    public static function GetArbiter()
    {
        return self::GetNode(['rank' => 'arbiter']);
    }

    public static function GetValidatorAddress()
    {
        return self::GetNodeAddress(['rank' => 'validator']);
    }

    public static function GetSupervisorAddress()
    {
        return self::GetNodeAddress(['rank' => 'supervisor']);
    }

    public static function GetArbiterAddress()
    {
        return self::GetNodeAddress(['rank' => 'arbiter']);
    }

    public static function GetFullNodeAddress()
    {
        $query = [
            'rank' => [
                '$in' => [
                    'validator',
                    'supervisor',
                    'arbiter',
                ]
            ]
        ];

        return self::GetNodeAddress($query);
    }

    public static function GetAdmittedValidator()
    {
        $query = [
            'rank' => 'validator',
            'status' => 'admitted',
            'host' => [
                '$nin' => [
                    null,
                    ''
                ]
            ]
        ];

        return self::GetNode($query);
    }

    public static function GetAdmittedSupervisor()
    {
        $query = [
            'rank' => 'supervisor',
            'status' => 'admitted',
        ];

        return self::GetNode($query);
    }

    public static function GetAdmittedArbiter()
    {
        $query = [
            'rank' => 'arbiter',
            'status' => 'admitted',
        ];

        return self::GetNode($query);
    }

    public static function GetAdmittedFullNode()
    {
        $validators = self::GetAdmittedValidator();
        $supervisors = self::GetAdmittedSupervisor();
        $arbiters = self::GetAdmittedArbiter();

        return array_merge($validators, $supervisors, $arbiters);
    }

    public static function GetAccessibleValidator()
    {
        $query = [
            'rank' => 'validator',
            'my_observed_status' => 'admitted',
            'host' => [
                '$nin' => [
                    null,
                    ''
                ]
            ]
        ];

        return self::GetNode($query);
    }

    public static function GetAccessibleSupervisor()
    {
        $query = [
            'rank' => 'supervisor',
            'host' => [
                '$nin' => [
                    null,
                    ''
                ]
            ]
        ];

        return self::GetNode($query);
    }

    public static function GetAccessibleArbiter()
    {
        $query = [
            'rank' => 'arbiter',
            'host' => [
                '$nin' => [
                    null,
                    ''
                ]
            ]
        ];

        return self::GetNode($query);
    }

    public static function GetAccessibleFullNode()
    {
        $validators = self::GetAccessibleValidator();
        $supervisors = self::GetAccessibleSupervisor();
        $arbiters = self::GetAccessibleArbiter();

        return array_merge($validators, $supervisors, $arbiters);
    }

    public static function GetAdmittedValidatorAddress()
    {
        $query = [
            'rank' => 'validator',
            'status' => 'admitted',
            'host' => [
                '$nin' => [
                    null,
                    ''
                ]
            ]
        ];

        return self::GetNodeAddress($query);
    }

    public static function GetAdmittedSupervisorAddress()
    {
        $query = [
            'rank' => 'supervisor',
            'status' => 'admitted',
        ];

        return self::GetNodeAddress($query);
    }

    public static function GetAdmittedArbiterAddress()
    {
        $query = [
            'rank' => 'arbiter',
            'status' => 'admitted',
        ];

        return self::GetNodeAddress($query);
    }

    public static function GetAdmittedFullNodeAddress()
    {
        $validators = self::GetAdmittedValidatorAddress();
        $supervisors = self::GetAdmittedSupervisorAddress();
        $arbiters = self::GetAdmittedArbiterAddress();

        return array_merge($validators, $supervisors, $arbiters);
    }

    public static function IsValidator($address)
    {
        return self::IsNode($address, ['rank' => 'validator']);
    }

    public static function IsSupervisor($address)
    {
        return self::IsNode($address, ['rank' => 'supervisor']);
    }

    public static function IsArbiter($address)
    {
        return self::IsNode($address, ['rank' => 'arbiter']);
    }

    public static function IsFullNode($address)
    {
        $query = [
            'rank' => [
                '$in' => [
                    'validator',
                    'supervisor',
                    'arbiter',
                ]
            ]
        ];

        return self::IsNode($address, $query);
    }

    public static function IsAdmittedValidator($address)
    {
        $query = [
            'rank' => 'validator',
            'status' => 'admitted',
            'host' => [
                '$nin' => [
                    null,
                    ''
                ]
            ]
        ];

        return self::IsNode($address, $query);
    }

    public static function IsAdmittedSupervisor($address)
    {
        $query = [
            'rank' => 'supervisor',
            'status' => 'admitted',
        ];

        return self::IsNode($address, $query);
    }

    public static function IsAdmittedArbiter($address)
    {
        $query = [
            'rank' => 'arbiter',
            'status' => 'admitted',
        ];

        return self::IsNode($address, $query);
    }

    public static function IsAdmittedFullNode($address)
    {
        $query = [
            'rank' => [
                '$in' => [
                    'validator',
                    'supervisor',
                    'arbiter',
                ]
            ],
            'status' => 'admitted',
        ];

        return self::IsNode($address, $query);
    }

    public static function Upsert($host, $address, $role, $status = 'admitted')
    {
        $db = Database::GetInstance();
        $filter = ['address' => $address];

        $item = [
            '$set' => [
                'host' => $host,
                'rank' => $role,
                'status' => $status,
            ]
        ];

        $opt = ['upsert' => true];
        $db->bulk->update($filter, $item, $opt);

        $db->BulkWrite(self::$namespace_tracker);
    }

    public static function Banish($address)
    {
        $db = Database::GetInstance();
        $filter = ['address' => $address];

        $item = [
            '$set' => [
                'status' => 'banished',
            ]
        ];

        $opt = ['upsert' => true];
        $db->bulk->update($filter, $item, $opt);

        $db->BulkWrite(self::$namespace_tracker);
    }

    public static function Admit($address)
    {
        $db = Database::GetInstance();
        $filter = ['address' => $address];

        $item = [
            '$set' => [
                'status' => 'admitted',
            ]
        ];

        $opt = ['upsert' => true];
        $db->bulk->update($filter, $item, $opt);

        $db->BulkWrite(self::$namespace_tracker);
    }

    public static function SetObservedStatus($host, $status)
    {
        $db = Database::GetInstance();
        $filter = ['host' => $host];

        $item = [
            '$set' => [
                'my_observed_status' => $status,
            ]
        ];

        $db->bulk->update($filter, $item);

        $db->BulkWrite(self::$namespace_tracker);
    }

    public static function SetHost($address, $host)
    {
        $db = Database::GetInstance();
        $filter = ['address' => $address];

        $item = [
            '$set' => [
                'host' => $host,
            ]
        ];

        $opt = ['upsert' => true];
        $db->bulk->update($filter, $item, $opt);

        $db->BulkWrite(self::$namespace_tracker);
    }

    public static function SetValidator($address)
    {
        $db = Database::GetInstance();
        $filter = ['address' => $address];

        $item = [
            '$set' => [
                'rank' => 'validator',
                'status' => 'admitted',
            ]
        ];

        $opt = ['upsert' => true];
        $db->bulk->update($filter, $item, $opt);

        $db->BulkWrite(self::$namespace_tracker);
    }

    public static function SetSupervisor($address)
    {
        $db = Database::GetInstance();
        $filter = ['address' => $address];

        $item = [
            '$set' => [
                'rank' => 'supervisor',
                'status' => 'none',
            ]
        ];

        $opt = ['upsert' => true];
        $db->bulk->update($filter, $item, $opt);

        $db->BulkWrite(self::$namespace_tracker);
    }

    public static function SetArbiter($address)
    {
        $db = Database::GetInstance();
        $filter = ['address' => $address];

        $item = [
            '$set' => [
                'rank' => 'arbiter',
                'status' => 'none',
            ]
        ];

        $opt = ['upsert' => true];
        $db->bulk->update($filter, $item, $opt);

        $db->BulkWrite(self::$namespace_tracker);
    }

    public static function SetLightNode($address)
    {
        $db = Database::GetInstance();
        $filter = ['address' => $address];

        $item = [
            '$set' => [
                'rank' => 'light',
                'status' => 'none',
            ]
        ];

        $opt = ['upsert' => true];
        $db->bulk->update($filter, $item, $opt);

        $db->BulkWrite(self::$namespace_tracker);
    }

    public static function GetRole($address)
    {
        $db = Database::GetInstance();
        $role = 'light';
        $query = ['address' => $address];

        $rs = $db->Query(self::$namespace_tracker, $query);

        foreach ($rs as $item) {
            $role = $item->rank;

            break;
        }

        return $role;
    }

    public static function GetRandomValidator()
    {
        $validators = self::GetAdmittedValidator();
        $count = count($validators);
        $pick = rand(0, $count - 1);

        return $validators[$pick];
    }
}
