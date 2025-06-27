<?php
namespace Xanka\SplitCart\Controller\Cart;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Staging\Controller\Result\JsonFactory;
use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * Class UpdateItemSelection
 *
 * @package Xanka\SplitCart\Controller\Cart
 */
class UpdateItemSelection extends Action implements HttpPostActionInterface
{
    protected $jsonFactory;
    protected $checkoutSession;
    protected $request;
    protected $formKeyValidator;
    private ?JsonHelper $helper;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        CheckoutSession $checkoutSession,
        RequestInterface $request
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->checkoutSession = $checkoutSession;
        $this->request = $request;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $credentials = json_decode($this->getRequest()->getContent());
        try {
            $itemId = $credentials->item_id;
            $isSelected = $credentials->is_selected;
            $quote = $this->checkoutSession->getQuote();
            $item = $quote->getItemById($itemId);
            if ($item) {
                $item->addData([
                    'selected_item' => $isSelected,
                ]);
                $quote->save();
                $result->setData(['success' => true, 'message' => __('Item selection updated successfully.')]);
            } else {
                $result->setData(['success' => false, 'message' => __('Item not found. %1', $itemId)]);
            }
        } catch (\Exception $e) {
            $result->setData(['success' => false, 'message' => __('An error occurred while updating item selection: %1', $e->getMessage())]);
        }
        return $result;
    }

}
