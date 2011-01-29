<?php
/** 
 * LICENSE
 *
 * This source file is subject to the new BSD license.
 * It is available through the world-wide-web at this URL:
 * http://www.liquidbytes.net/bsd.html
 *
 * @category   Liquid
 * @package    Liquid_Storage
 * @copyright  Copyright (c) 2010 Liquid Bytes Technologies (http://www.liquidbytes.net/)
 * @license    http://www.liquidbytes.net/bsd.html New BSD License
 */

interface Liquid_Storage_Interface {
    // Namespace meta data
    public function setNamespaceMeta ($namespace, array $meta);
    public function getNamespaceMeta ($namespace);
    public function addNamespaceMeta ($namespace, $name, $value);
    public function replaceNamespaceMeta ($namespace, $name, $value);
    public function deleteNamespaceMeta ($namespace, $name);

    // Key meta data
    public function setKeyMeta ($namespace, $key, array $meta);
    public function getKeyMeta ($namespace, $key);
    public function addKeyMeta ($namespace, $key, $name, $value);
    public function replaceKeyMeta ($namespace, $key, $name, $value);
    public function deleteKeyMeta ($namespace, $key, $name);

    // Entry meta data
    public function setEntryMeta (Liquid_Storage_Entry $entry, array $meta);
    public function getEntryMeta (Liquid_Storage_Entry $entry);
    public function addEntryMeta (Liquid_Storage_Entry $entry, $name, $value);
    public function replaceEntryMeta (Liquid_Storage_Entry $entry, $name, $value);
    public function deleteEntryMeta (Liquid_Storage_Entry $entry, $name);   

    // Returns list of all namespaces
    public function getNamespaces ($flat = true);
    
    // Checks if namespace/key/entry exists in store
    public function namespaceExists ($namespace); // Tested by testNamespaceExists()
    public function keyExists ($namespace, $key); // Tested by testKeyExists()
    public function entryExists (Liquid_Storage_Entry $entry);

    // Return entries or list of entries
    public function findKeys ($namespace, $flat = true);
    public function findIndex ($namespace, $key);
    public function findById ($id);
    public function findByMeta ($name, $value);
    public function findOne ($namespace, $key, $id); 
    public function findFirst ($namespace, $key);
    public function findLast ($namespace, $key); 
    
    // Refresh entry with latest data from the store
    public function refreshEntry (Liquid_Storage_Entry $entry);  

    // Create new version
    public function createEntry(Liquid_Storage_Entry $entry);
    
    // Replace latest version or create fist version, if no entry exists yet
    public function replaceEntry (Liquid_Storage_Entry $entry);
    
    // Write updated entry data to the store
    public function updateEntry (Liquid_Storage_Entry $entry);
    
    // Delete data from store (be careful with deleteAll(), deleteNamespace() and deleteKey()!)
    public function deleteAll ();
    public function deleteNamespace ($namespace);
    public function deleteKey ($namespace, $key);
    public function deleteEntry (Liquid_Storage_Entry $entry);
    
    // Rename namespaces and keys (can take a long time, depending on backend)
    public function renameNamespace ($oldNamespaceName, $newNamespaceName);
    public function renameKey ($namespace, $oldKeyName, $newKeyName);
}
