<?php
class ModelExtensionModuleBulkProductUpdate extends Model 
{
    public function updatePrice($data)
    {
        $sql = "UPDATE " . DB_PREFIX . "product SET price = " . (float)$data['price'] . ", date_modified = NOW() WHERE product_id = '" . (int)$data['product_id'] . "'";
        $this->db->query($sql);
    }
}