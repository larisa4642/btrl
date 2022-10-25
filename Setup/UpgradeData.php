<?php
 
namespace btrl\ipay\Setup;
 
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Sales\Setup\SalesSetupFactory;
 
/**
 * Class UpgradeData
 *
 * @package btrl\ipay\Setup
 */
class UpgradeData implements UpgradeDataInterface
{
    private $salesSetupFactory;
 
    /**
     * Constructor
     *
     * @param \Magento\Sales\Setup\SalesSetupFactory $salesSetupFactory
     */
    public function __construct(SalesSetupFactory $salesSetupFactory)
    {
        $this->salesSetupFactory = $salesSetupFactory;
    }
 
    /**
     * {@inheritdoc}
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        if (version_compare($context->getVersion(), "1.0.1", "<")) {
            $salesSetup = $this->salesSetupFactory->create(array('setup' => $setup)); 
			
        $options = array('type' => 'varchar', 'length'=> 255, 'visible' => false, 'required' => false, 'grid' => true);
        $salesSetup->addAttribute('order', 'ipay_status', $options);
        $salesSetup->addAttribute('order', 'ipay_id', $options);
			
		 }
    }
} 