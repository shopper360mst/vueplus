<?php

namespace App\Repository;

use App\Entity\Product;
use App\AppBundle\AppParams;
use Psr\Log\LoggerInterface;

use App\Service\CipherService;
use App\Repository\Traits\AssigneeTrait;
use App\AppBundle\Util\SQLExtraHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use App\AppBundle\DevExtremePlus\QuerySetter;
use App\AppBundle\DevExtremePlus\DataSourceLoader;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityNotFoundException;

class ProductRepository extends ServiceEntityRepository
{
    use AssigneeTrait;
    
    private $TABLE_NAME = 'product';
    private $KEY_COLUMN = 'id';

    /* NO SEMICOLON CAN BE USED FOR STARTER QUERY */
    private $STARTER_QUERY = '
        SELECT
        p.id,
        p.product_code,
        p.product_name,
        p.product_category,
        p.product_type,
        p.product_sku,
        p.expiry_date,
        p.is_locked,
        p.locked_date,
        p.is_collected,
        p.collected_date,
        p.updated_date,
        p.created_date,
        p.delivered_date,
        p.details_updated_date,
        p.product_photo,
        p.is_contacted,
        p.user_id,
        COALESCE(AES_DECRYPT(FROM_BASE64(u.full_name), "shopper1100", "??", "aes-256-cbc"), "") as winner_name,
        p.delivery_status,
        p.courier_details,
        AES_DECRYPT(FROM_BASE64(p.receiver_full_name), "shopper1100", "??", "aes-256-cbc") as receiver_name,
        AES_DECRYPT(FROM_BASE64(p.receiver_mobile_no), "shopper1100", "??", "aes-256-cbc") as receiver_mobile_no,
        "" as receiver_email,
        AES_DECRYPT(FROM_BASE64(p.address1), "shopper1100", "??", "aes-256-cbc") as delivery_address_1,
        AES_DECRYPT(FROM_BASE64(p.address2), "shopper1100", "??", "aes-256-cbc") as delivery_address_2,
        p.city as delivery_city,
        p.state as delivery_state,
        p.postcode as delivery_postcode,
        DATE(p.due_date) as due_date
        FROM product p
        LEFT JOIN user u ON p.user_id = u.id
        WHERE (p.is_deleted IS NULL OR p.is_deleted != 1)
    ';
    private $exportQueryFinalWFilter = '
    SELECT * FROM (SELECT
    p.id,
    p.updated_date,
    AES_DECRYPT(FROM_BASE64(u.full_name), "shopper1100", "??", "aes-256-cbc") as winner_name,
    p.delivery_status,
    p.courier_details,
    DATE(p.due_date) as due_date,
    AES_DECRYPT(FROM_BASE64(p.receiver_full_name), "shopper1100", "??", "aes-256-cbc") as receiver_full_name,
    AES_DECRYPT(FROM_BASE64(p.receiver_mobile_no), "shopper1100", "??", "aes-256-cbc") as receiver_mobile_no,
    AES_DECRYPT(FROM_BASE64(p.address1), "shopper1100", "??", "aes-256-cbc") as address_1,
    AES_DECRYPT(FROM_BASE64(p.address2), "shopper1100", "??", "aes-256-cbc") as address_2,
    p.postcode,
    p.product_code,
    p.city,
    p.state
    FROM product p
    INNER JOIN user u
    ON p.user_id = u.id
    WHERE p.delivery_status IN ("PROCESSING")
    ) BIGTABLE WHERE due_date = ? ORDER BY due_date desc
    ';

    public function __construct(ManagerRegistry $registry, CipherService $cs) {
        /* FOR ENCRYPTION */
        $this->KEY = $_SERVER['APP_SECRET'];
        $this->SALT = $_SERVER['SALT_KEY'];
        $this->cs = $cs;
        parent::__construct($registry, Product::class);
    }

    public function update(int $entityId, array $params): object
    {
        if (!$entity = $this->find($entityId)) throw new EntityNotFoundException('Entity not found');

        $this->attributeParameterAssignee($entity, $params);

        $this->getEntityManager()->flush();

        return $entity;
    }

    public function findAndLock($user, $product_category, $region = null ) {
        $conn = $this->getEntityManager()->getConnection();
        
        if ($region) {
            $sql = 'SELECT * FROM product WHERE product_category = ? AND region = ? AND is_locked = 0 AND user_id IS null AND NOW() >= created_date ORDER BY id ASC LIMIT 1';
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(1, $product_category);
            $stmt->bindValue(2, $region);
        } else {
            $sql = 'SELECT * FROM product WHERE product_category = ? AND is_locked = 0 AND user_id IS null AND NOW() >= created_date ORDER BY id ASC LIMIT 1';
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(1, $product_category);
        }
        
        $res = $stmt->executeQuery();

        $freeProduct = $res->fetchAllAssociative();
        if (count ( $freeProduct )) {
            $productEntity = $this->find($freeProduct[0]['id']);
            $productEntity->setUser($user);
            $productEntity->setLocked(1);

            // BUSINESS LOGIC
            // $productEntity->setIsCollected(1);
            // if ( $productEntity->getProductType() == 'PRODUCT') {
            //     $productEntity->setDeliveryStatus('ADDRESS');
            // }
            // BUSINESS LOGIC

            $productEntity->setLockedDate(new \DateTime);
            $this->getEntityManager()->persist($productEntity);
            $this->getEntityManager()->flush();

            return $productEntity->getId();
        } else {
            return 0;
        }
    }


    public function getAll($params) {
         /****************************** SAMPLE DG GET PARAMS PAYLOAD ************************
        Array (
            [querystring1] => query_string_value1
            [querystring2] => query_string_value2
            [skip] => 0
            [take] => 20
            [requireTotalCount] => 1
            [filter] => Array (
                [0] => Array (
                    [0] => dx_col_name1
                    [1] => contains
                    [2] => value1
                )
                [1] => and
                [2] => Array (
                    [0] => dx_col_name2
                    [1] => contains
                    [2] => value2
                )
            )
            [_] => <RUNNING_HASHES#>
        )
        ****************************** SAMPLE DG GET PARAMS PAYLOAD ************************/
        $conn = $this->getEntityManager()->getConnection();
        $this->dbSet = new QuerySetter($conn, $this->STARTER_QUERY, $this->TABLE_NAME);

        /*********** REST OF PARAM LIKE [FILTER] WILL BE PROCESSED HERE ********************/
        $result = DataSourceLoader::Load($this->dbSet, $params);
        return $result;
    }

    public function getExport($date = null) {
        $conn = $this->getEntityManager()->getConnection();
        $iv = substr($this->KEY, 0, 16); // First 16 characters of APP_SECRET for IV

        if(isset($date)){
            $final_sql = str_replace("??", $iv, $this->exportQueryFinalWFilter);
            $stmt = $conn->prepare($final_sql);
            $stmt->bindParam(1, $date);
            $query = $stmt->executeQuery();
            $result = $query->fetchAllAssociative();
        }
        else{
            $final_sql = str_replace("??", $iv, $this->STARTER_QUERY);
            $stmt = $conn->prepare($final_sql);
            $query = $stmt->executeQuery();
            $result = $query->fetchAllAssociative();
        }
        return $result;
    }

    /**
     * Map frontend field names to database column names
     *
     * @param array $data The data array with frontend field names
     * @return array The data array with database column names
     */
    private function mapFrontendFieldsToDatabase(array $data): array
    {
        $fieldMappings = [
            'receiver_name'      => 'receiver_full_name',
            'delivery_address_1' => 'address1',
            'delivery_address_2' => 'address2',
            'delivery_city'      => 'city',
            'delivery_state'     => 'state',
            'delivery_postcode'  => 'postcode'
        ];

        foreach ($fieldMappings as $frontendField => $dbField) {
            if (isset($data[$frontendField])) {
                $data[$dbField] = $data[$frontendField];
                unset($data[$frontendField]);
            }
        }

        return $data;
    }

    public function findAndUpdate($params) {
        $processed = QuerySetter::GetParamsFromInput($params);
        $id = $processed['key'];
        $conn = $this->getEntityManager()->getConnection();
        $incomingData = $processed['values'];

        // Map frontend field names to database column names
        $incomingData = $this->mapFrontendFieldsToDatabase($incomingData);

        if(isset($incomingData['courier_details'])){
            $incomingData['delivery_status'] = 'OUT FOR DELIVERY';
        }

        // pre-set the updated_date field
        $incomingData['updated_date'  ] = $incomingData['updated_date'] ?? date('Y-m-d H:i:s');

        $product = $this->find($id);

        $isChangedToOutForDelivery = isset($incomingData['courier_status']) && $incomingData['courier_status'] == 'OUT FOR DELIVERY';
        $isChangedToProcessing     = isset($incomingData['courier_status']) && $incomingData['courier_status'] == 'PROCESSING' && $product->getCourierStatus() != $incomingData['courier_status'];
        $isChangedFromReturned     = $isChangedToProcessing && $product->getCourierStatus() == 'RETURNED';

        if($isChangedToOutForDelivery){ $incomingData['delivered_date' ] = date('Y-m-d H:i:s'); }
        if($isChangedToProcessing    ){ 
            $incomingData['due_date'       ] = date('Y-m-d', strtotime('+5 days'));
            $incomingData['is_collected']   = 0;
            $incomingData['collected_date'] = null;
        }
        if($isChangedFromReturned    ){ $incomingData['delivery_status'] = 'PROCESSING'; }

        $setterStmts = SQLExtraHelper::convertArrayToSet($incomingData);
        $sql = "UPDATE $this->TABLE_NAME SET $setterStmts WHERE id = ?";

        $stmt = $conn->prepare($sql);
        $i = 1;
        foreach ($incomingData as $key => $value) {
            if (
                   ($key == "mobile_no"         )
                || ($key == "full_name"         )
                || ($key == "email"             )
                || ($key == "national_id"       )
                || ($key == "address1"          )
                || ($key == "address2"          )
                || ($key == "receiver_full_name")
                || ($key == "receiver_mobile_no")
            ) {
                // STANDARD ENCRYPTION FIELDS.
                $encryptedValue = $this->cs->encrypt($value);
            } else {
                $encryptedValue = $value;
            }
            $stmt->bindValue($i, $encryptedValue);
            $i++;
        }
        $stmt->bindParam(count($incomingData) + 1, $id );
        $result = $stmt->executeQuery();

        return $result->fetchAllAssociative();
    }

    public function findAndDelete($params) {
        $id = QuerySetter::GetParamsFromInput($params)['key'];
        $resEnt = $this->find($id);
        $this->getEntityManager()->remove($resEnt);
        $this->getEntityManager()->flush();
        return true;
    }

    /**
     * Get export data for products
     */
    public function getExportData($params = null)
    {
        $conn = $this->getEntityManager()->getConnection();
        $iv = substr($this->KEY, 0, 16); // First 16 characters of APP_SECRET for IV

        $sql = '
            SELECT
                p.id,
                p.product_code,
                p.product_name,
                AES_DECRYPT(FROM_BASE64(p.receiver_full_name), "shopper1100", "' . $iv . '", "aes-256-cbc") as receiver_name,
                AES_DECRYPT(FROM_BASE64(p.receiver_mobile_no), "shopper1100", "' . $iv . '", "aes-256-cbc") as receiver_mobile_no,
                AES_DECRYPT(FROM_BASE64(p.address1), "shopper1100", "' . $iv . '", "aes-256-cbc") as delivery_address_1,
                AES_DECRYPT(FROM_BASE64(p.address2), "shopper1100", "' . $iv . '", "aes-256-cbc") as delivery_address_2,
                p.city as delivery_city,
                p.state as delivery_state,
                p.postcode as delivery_postcode,
                p.courier_status,
                p.delivery_assign,
                DATE_FORMAT(p.details_updated_date, "%Y-%m-%d %H:%i:%s") as details_updated_date,
                DATE(p.due_date) as due_date
            FROM product p
            WHERE (p.is_deleted IS NULL OR p.is_deleted != 1) AND (p.is_collected = 0 OR p.is_collected IS NULL)';

        $whereRoles = '';
        if($params['roles'] == 'ROLE_CC'     ) $whereRoles = ' AND (p.courier_status = "INCOMPLETE INFO" OR p.courier_status = "RETURNED") AND p.delivery_assign = "CC"';
        if($params['roles'] == 'ROLE_TRISTAR') $whereRoles = ' AND p.courier_status = "PROCESSING" AND p.delivery_assign = "TS"';
        if($params['roles'] == 'ROLE_GDEX'   ) $whereRoles = ' AND p.courier_status = "PROCESSING" AND p.delivery_assign = "GDEX"';
        if($params['roles'] == 'ROLE_SMX'    ) $whereRoles = ' AND p.courier_status = "PROCESSING" AND p.delivery_assign = "SMX"';

        $whereDueDate = !isset($params['due_date']) ? "" : " AND due_date BETWEEN '{$params['due_date_from']}' AND '{$params['due_date_to']}'";

        $order = ' ORDER BY p.id DESC ';

        $sql = $sql . $whereRoles . $whereDueDate . $order;

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();
        return $result->fetchAllAssociative();
    }

    /**
     * Find products by courier details
     */
    public function findByCourierDetails($courierDetails)
    {
        $conn = $this->getEntityManager()->getConnection();
        $iv = substr($this->KEY, 0, 16); // First 16 characters of APP_SECRET for IV

        $sql = '
            SELECT
                p.id,
                p.product_code,
                p.product_name,
                p.product_category,
                p.product_type,
                p.product_sku,
                p.expiry_date,
                p.is_locked,
                p.locked_date,
                p.is_collected,
                p.collected_date,
                p.updated_date,
                p.created_date,
                p.delivered_date,
                p.details_updated_date,
                p.product_photo,
                p.is_contacted,
                p.user_id,
                COALESCE(AES_DECRYPT(FROM_BASE64(u.full_name), "shopper1100", "' . $iv . '", "aes-256-cbc"), "") as winner_name,
                p.delivery_status,
                p.courier_status,
                p.courier_details,
                AES_DECRYPT(FROM_BASE64(p.receiver_full_name), "shopper1100", "' . $iv . '", "aes-256-cbc") as receiver_name,
                AES_DECRYPT(FROM_BASE64(p.receiver_mobile_no), "shopper1100", "' . $iv . '", "aes-256-cbc") as receiver_mobile_no,
                "" as receiver_email,
                AES_DECRYPT(FROM_BASE64(p.address1), "shopper1100", "' . $iv . '", "aes-256-cbc") as delivery_address_1,
                AES_DECRYPT(FROM_BASE64(p.address2), "shopper1100", "' . $iv . '", "aes-256-cbc") as delivery_address_2,
                p.city as delivery_city,
                p.state as delivery_state,
                p.postcode as delivery_postcode,
                DATE(p.due_date) as due_date,
                p.return_remarks
            FROM product p
            LEFT JOIN user u ON p.user_id = u.id
            WHERE (p.is_deleted IS NULL OR p.is_deleted != 1)
                AND p.courier_details LIKE ?
            ORDER BY p.created_date DESC
        ';

        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, '%' . $courierDetails . '%');
        $result = $stmt->executeQuery();
        return $result->fetchAllAssociative();
    }

    public function getLockedProductsByCategory()
    {
        $conn = $this->getEntityManager()->getConnection();
        $categories = ['LUGGAGE_SHM', 'GRILL_SHM', 'RUMMY_SHM'];
        $regions = ['em', 'wm'];
        $result = [];

        foreach ($categories as $category) {
            foreach ($regions as $region) {
                $sql = 'SELECT COUNT(*) as cnt FROM product WHERE product_category = ? AND region = ? AND is_locked = 0';
                $stmt = $conn->prepare($sql);
                $stmt->bindValue(1, $category);
                $stmt->bindValue(2, $region);
                $query = $stmt->executeQuery();
                $count = $query->fetchAssociative();

                if ($count['cnt'] == 0) {
                    $result[$region][] = $category;
                }
            }
        }

        return $result;
    }

    public function markAsCollected(array $productIds): void
    {
        if (empty($productIds)) {
            return;
        }

        $conn = $this->getEntityManager()->getConnection();
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        
        $sql = "UPDATE product SET is_collected = 1, collected_date = NOW() WHERE id IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        
        foreach ($productIds as $index => $id) {
            $stmt->bindValue($index + 1, $id);
        }
        
        $stmt->executeQuery();
    }
}