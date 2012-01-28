<?php
/**
 * Tag - a content-tagging module for the Zikukla Application Framework
 * 
 * @license MIT
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */

/**
 * Installer.
 */
class Tag_Installer extends Zikula_AbstractInstaller
{

    /**
     * Install the module.
     *
     * @return boolean True on success, false otherwise.
     */
    public function install()
    {
        // create the table
        try {
            DoctrineHelper::createSchema($this->entityManager, array('Tag_Entity_Tag', 'Tag_Entity_Object'));
        } catch (Exception $e) {
            LogUtil::registerError($e->getMessage());
            return false;
        }
        
        $this->setVars(array(
            'poptagsoneditform' => 10,
            'crpTagMigrateComplete' => false,
        ));

        $this->defaultdata();
        
        HookUtil::registerProviderBundles($this->version->getHookProviderBundles());
        EventUtil::registerPersistentModuleHandler('Tag', 'installer.module.uninstalled', array('Tag_HookHandlers', 'moduleDelete'));
        EventUtil::registerPersistentModuleHandler('Tag', 'module.content.gettypes', array('Tag_Handlers', 'getTypes'));
        EventUtil::registerPersistentModuleHandler('Tag', 'view.init', array('Tag_Handlers', 'registerPluginDir'));

        // Initialisation successful
        return true;
    }

    /**
     * Upgrade the module from an old version.
     *
     * This function can be called multiple times.
     *
     * @param integer $oldversion Version to upgrade from.
     *
     * @return boolean True on success, false otherwise.
     */
    public function upgrade($oldversion)
    {
        switch ($oldversion)
        {
            case '1.0.0':
                // update the table
                try {
                    DoctrineHelper::updateSchema($this->entityManager, array('Tag_Entity_Tag'));
                } catch (Exception $e) {
                    LogUtil::registerError($e->getMessage());
                    return false;
                }
                // update existing tags to include slug
                $tags = $this->entityManager->getRepository('Tag_Entity_Tag')->findAll();
                foreach ($tags as $tag) {
                    $tag->setTag($tag->getTag());
                    $this->entityManager->persist($tag);
                }
                $this->entityManager->flush();
                // correct/remove orphaned entries in `tag_entity_object_tag_entity_tag`
                $conn = $this->entityManager->getConnection();
                $sql = "SELECT from tag_entity_object_tag_entity_tag";
                $results = $conn->fetchAll($sql);
                foreach ($results as $result) {
                    $match = $this->entityManager->getRepository('Tag_Entity_Object')->findOneBy(array('id' => $result['tag_entity_object_id']));
                    if (!$match) {
                        $sql = "DELETE from tag_entity_object_tag_entity_tag WHERE tag_entity_object_id=" . $result['tag_entity_object_id'];
                        $conn->query($sql);
                    }
                }
                
            case '1.0.1':
                // future upgrades
        }

        // Update successful
        return true;
    }

    /**
     * Uninstall the module.
     *
     * This function is only ever called once during the lifetime of a particular
     * module instance.
     *
     * @return bool True on success, false otherwise.
     */
    public function uninstall()
    {
        // drop tables
        DoctrineHelper::dropSchema($this->entityManager, array('Tag_Entity_Tag', 'Tag_Entity_Object'));
        
        // unregister handlers
        EventUtil::unregisterPersistentModuleHandlers('Tag');
        HookUtil::unregisterProviderBundles($this->version->getHookProviderBundles());
        
        // remove all module vars
        $this->delVars();

        // Deletion successful
        return true;
    }

    /**
     * Provide default data.
     *
     * @return void
     */
    protected function defaultdata()
    {
        foreach (array('Zikula', 'computer', 'open source') as $word) {
            $tag = new Tag_Entity_Tag();
            $tag->setTag($word);
            $this->entityManager->persist($tag);
        }
        $this->entityManager->flush();
    }
}