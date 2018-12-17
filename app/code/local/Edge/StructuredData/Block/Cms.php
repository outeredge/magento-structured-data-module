<?php
class Edge_StructuredData_Block_Cms extends Mage_Core_Block_Template
{
    /**
     * @var string
     */
    protected $_image = null;

    /**
     * @return bool|string
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getImage()
    {
        if ($this->_image === null) {
            $this->_image = false;

            foreach (['image', 'small_image', 'thumbnail', 'primary_image', 'secondary_image', 'tertiary_image'] as $image) {
                if (Mage::getSingleton('cms/page')->getData($image)) {
                    $this->_image = Mage::getSingleton('cms/page')->getData($image);
                    break;
                }
            }

            if (!$this->_image) {
                $this->setImageFromContent();
            }

            if ($this->_image) {
                if (!preg_match('/^http/', $this->_image)) {
                    if (preg_match('/^\/?media/', $this->_image)) {
                        $this->_image = Mage::app()->getStore()->getBaseUrl() . $this->_image;
                    } else {
                        $this->_image = Mage::app()->getStore()->getBaseUrl('media') . $this->_image;
                    }
                }
            }
        }
        return $this->_image;
    }

    /**
     * Assign value to the $_image.
     */
    protected function setImageFromContent()
    {
        $doc = new DOMDocument();
        @$doc->loadHtml(Mage::getSingleton('cms/page')->getContent());
        $tags = $doc->getElementsByTagName('img');
        if ($tags->length > 0) {
            foreach ($tags as $tag) {
                $this->_image = $tag->getAttribute('src');
                break;
            }
        }
    }
}