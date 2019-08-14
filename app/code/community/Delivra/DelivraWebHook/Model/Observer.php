<?php 

class Delivra_DelivraWebHook_Model_Observer {

    /**
     *   Respond to a order save event
     */
    public function saveOrder($observer) {
        $order = $observer->getEvent()->getOrder();
        $this->handleOrderUpdate($order, $order->getStatus());
        return $this;
    }

    /**
     *   Respond to a new order event
     */
    public function postOrder($observer) {
        //$this->handleOrderUpdate($observer->getEvent()->getOrder(), "pending");
        return $this;
    }

    /**
     *   Respond to a cancel order event
     */
    public function cancelOrder($observer) {
        //$this->handleOrderUpdate($observer->getPayment()->getOrder(), "cancelled");
        return $this;
    }

    /**
     *   Respond to a fulfill order event
     */
    public function fulfillOrder($observer) {
        //$this->handleOrderUpdate($observer->getEvent()->getShipment()->getOrder(), "fulfilled");
        return $this;
    }

    /**
     *   Respond to a customer update event
     */
    public function updateCustomer($observer) {
        try {
            $customer = $observer->getEvent()->getCustomer();
            $customerData = $customer->getData();
            $address = $customer->getPrimaryBillingAddress();
            if ($address) {
                $customerData['primaryBillingAddress'] = $address->getData();
            }
            $webhookEnabled = Mage::getStoreConfig('delivrawebhook/customer/enable', $customerData['store_id']);
            if ($webhookEnabled) {
                $customerEvent = new stdClass;
                $customerEvent->data = $customerData;
                $customerEvent->event = "Update";
                $customerEvent->entityType = "customer";
                $queue = Mage::getModel('delivrawebhook/queue');
                $queue->enqueue($customerEvent);
            }
        } catch (Exception $ex) {
            Mage::logException($e);
        }

        return $this;
    }

    /**
     *  Respond to cron event. Process abandoned carts
     */
    public function processAbandonedCarts($schedule) {
        $adapter = Mage::getSingleton('core/resource')->getConnection('sales_read');
        $minuteSetting = Mage::app()->getWebsite()->getConfig('delivrawebhook/cart/delayMins');
        $minutes = intval($minuteSetting);
        
        if($minutes > 0){
            $from = $adapter->getDateSubSql(
                $adapter->quote(now()),
                $minutes,
                Varien_Db_Adapter_Interface::INTERVAL_MINUTE);
            $to = $adapter->getDateSubSql(
                $adapter->quote(now()),
                14,
                Varien_Db_Adapter_Interface::INTERVAL_DAY);
            $quotes = Mage::getModel('sales/quote')->getCollection()
                ->addFieldToFilter('converted_at', array('null' => true))
                ->addFieldToFilter('customer_email', array('notnull' => true))
                ->addFieldToFilter('items_count', array('gteq' => 1))
                ->addFieldToFilter('reserved_order_id', array('null' => true))
                ->addFieldToFilter('created_at', array('from' => $to))
                ->addFieldToFilter('updated_at', array('to' => $from));

            $quotes->getSelect()->joinLeft( array('cart' => 'delivrawebhook_cart'), 'main_table.entity_id = cart.quote_id', array('cart.cart_id'));
            $quotes->addFieldToFilter('cart.cart_id', array('null' => true));

            foreach($quotes as $quote) {
                $webhookEnabled = Mage::getStoreConfig('delivrawebhook/cart/enable', $quote['store_id']);
                if ($webhookEnabled) {
                    Mage::log('cart hook enabled');
                    $data = $quote->getData();
                    $data['url'] = Mage::getUrl('delivrawebhook/quote/restorecart',array('_nosid'=>true));
                    $data['url'].= '?quote='. $quote->getId();
                    $items = $quote->getAllItems();
                    foreach($items as $item) {
                        $itemData = array();
                        $product = Mage::getModel('catalog/product')->load($item->getProductId());
                        $itemData['entity_id'] = $item->getId();
                        $itemData['product_id'] = $item->getProductId();
                        $itemData['parent_item_id'] = $item->getParentItemId();
                        $itemData['name'] = $item->getName();
                        $itemData['created_at'] = $item->getCreatedAt();
                        $itemData['weight'] = $item->getWeight();
                        $itemData['sku'] = $product->getSku();
                        $productMediaConfig = Mage::getModel('catalog/product_media_config');
                        $itemData['image'] = Mage::getModel('catalog/product_media_config')->getMediaUrl( $product->getSmallImage() );
                        $itemData['qty_ordered'] = $item->getQty();
                        $itemData['price'] = $item->getPrice();
                        $itemData['description'] = $product->getDescription();
                        $categories = $product->getCategoryCollection()->addAttributeToSelect('name');
                        $categoryNames = array();
                        foreach($categories as $category) {
                            $categoryNames[] = $category->getName();
                        }

                        $itemData['categories'] = $categoryNames;
                        $itemData['size'] =  $product->getAttributeText('size');
                        $itemData['color'] = $product->getAttributeText("color"); 
                                
                        $data['line_items'][] = $itemData;
                    }
                    
                    $customerId = $quote->getCustomerId();
                    
                    if ($customerId) {
                        $customer = Mage::getModel('customer/customer')->load($customerId);
                        $data['customer'] = $customer->getData();
                        $data['customer']['customer_id'] = $quote->getCustomerId();
                        //$data['customer']['primaryBillingAddress'] = $customer->getPrimaryBillingAddress()->getData();
                        
                        $billing_address = $customer->getPrimaryBillingAddress();
                        if ($billing_address) {
                            $data['customer']['primaryBillingAddress'] = $billing_address->getData();
                        }

                        $data['url'].= '&id='. $customerId;
                    }else{
                        $data['customer'] = array();
                        $data['customer']['customer_id'] = $quote->getCustomerId();
                        $data['customer']['email'] = $quote->getCustomerEmail();
                        $data['customer']['firstname'] = $quote->getCustomerFirstname();
                        $data['customer']['lastname'] = $quote->getCustomerLastname();
                        $billing_address = $quote->getBillingAddress();
                        if ($billing_address) {
                            $data['customer']['primaryBillingAddress'] = $billing_address->getData();
                        }
                    }
                    
                    $cartEvent = new stdClass;
                    $cartEvent->data = $data;
                    $cartEvent->event = "Abandoned";
                    $cartEvent->entityType = "cart";
                    $queue = Mage::getModel('delivrawebhook/queue');
                    $queue->enqueue($cartEvent);
                    
                    //if successful add to cart table
                    $cart = Mage::getModel('delivrawebhook/cart');
                    $cart->addToCart($data);
                }
            }
        }else{
            Mage::log('abandoned cart delay not set');
        }
    }
    
    protected function cleanupQueue($queue) {
        $queue->cleanup();
    }

    /**
     *   Respond to cron event. Process pending queue items
     */
    public function scheduledSend($schedule) {
        $queue = Mage::getModel('delivrawebhook/queue');
        $pending = $queue->dequeueStart();
        $this->cleanupQueue($queue);
        if ($pending && !empty($pending)) {
            $connectedLists = Mage::helper('delivrawebhook/lists')->getConfigValues();
            foreach ($connectedLists as $listName => $apiKey) {
                foreach($pending as $key => $value) {
                    Mage::log('Delivra Queue - Processing item '.$key.' - '.$value['added_at'], null, 'delivra.log');
                    $urlBase = Mage::getStoreConfig('delivrawebhook/connection/url', $value['store_id']);
                    $url = $urlBase.'/WebHooks/Magento/Api/'.$value['entity_type'].'Event.ashx';
                    Mage::log($url);
                    
                    $headers = array('X-Delivra-Listname' => $listName, 'X-Delivra-Apikey' => $apiKey);

                    $message = "Delivra Web Hook\r\n";
                    try {
                        $response = $this->proxy($value['serialized_data'], $url, $headers);
                        if ($response->status != '200') {
                            throw new Exception($response->body);
                        }
                        $responseObj = json_decode($response->body);
                        $message = $message."Response:\r\n".$responseObj->d;
                        $queue->dequeueComplete($key);
                    } catch (Exception $ex) {
                        $message = $message."There was an error sending to Delivra.";
                        $attempts = $value['retry_count'];
                        $retry = $attempts <= 5;
                        $queue->dequeueError($key, $retry);

                        if ($retry) {
                            $message = $message." Retry scheduled.";
                        } else {
                            $message = $message." The maximum number of retry attempts has been exceeded. Giving up.";
                        }
                    }

                    if ($value['entity_type'] === 'order') {
                        $order = Mage::getModel('sales/order')->load($value['entity_id']);
                        $historyItem = $order->addStatusHistoryComment(
                        $message,
                        false);
                        $historyItem->setIsCustomerNotified(Mage_Sales_Model_Order_Status_History::CUSTOMER_NOTIFICATION_NOT_APPLICABLE);
                        $historyItem->save();

                        $order->save();
                    }
                }
            }
        }
    }

    /**
     *   Handle order events
     */
    private function handleOrderUpdate($order, $status) {
        try {
            $frontName = Mage::app()->getRequest()->getRouteName();
            if ($frontName) {
                $orderData = $this->transformOrder($order);
                $webhookEnabled = Mage::getStoreConfig('delivrawebhook/order/enable', $orderData['store_id']);

                if ($webhookEnabled) {
                    $webHookOrder = new stdClass;
                    $webHookOrder->data = $orderData;
                    $webHookOrder->event = $status;
                    $webHookOrder->entityType = "order";
                    $queue = Mage::getModel('delivrawebhook/queue');
                    $queue->enqueue($webHookOrder);
                }
            }
        } catch (Exception $ex) {
            Mage::logException($ex);
        }
    }

    /**
     *   Call remote api
     */
    private function proxy($data, $url, $headerValues) {
        $output = new stdClass();
        $ch = curl_init();
        $body = $data;

        $headers = array('Content-Type: application/json', 'Content-Length: '.strlen($body), 'Expect:');

        foreach($headerValues as $key => $value) {
            $headers[] = $key.': '.$value;
        }

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // ignore cert issues
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $output->status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $arr = explode("\r\n\r\n", $response, 2);
        if (count($arr) == 2) {
            $output->header = $arr[0];
            $output->body = $arr[1];
        } else {
            $output->body = "Unexpected response";
        }

        return $output;
    }

    /**
     *   Construct the payload for an order event service call
     */
    private function transformOrder($orderIn) {
        $orderOut = $orderIn->getData();
        $orderOut["status"] = $orderIn->getStatus();
        $orderOut['line_items'] = array();

        $productMediaConfig = Mage::getModel('catalog/product_media_config');
        $visibleItems = $orderIn->getAllItems();
        foreach($visibleItems as $item) {
            $itemData = $item->getData();
            $product = Mage::getModel('catalog/product')->load($item->getProductId());
            $categories = $product->getCategoryCollection()->addAttributeToSelect('name');
            $categoryNames = array();
            foreach($categories as $category) {
                $categoryNames[] = $category->getName();
            }

            $itemData['categories'] = $categoryNames;
            $itemData['imageUrl'] = $productMediaConfig->getMediaUrl($product->getSmallImage());
            $itemData['description'] = $product->getDescription();
            $itemData['status'] = $item->getStatus();
            $itemData['size'] =  $product->getAttributeText('size');
            $itemData['color'] = $product->getAttributeText("color"); 
            $orderOut['line_items'][] = $itemData;
        }
        $customerId = $orderIn->getCustomerId();
        if ($customerId) {
            $customer = Mage::getModel('customer/customer')->load($customerId);
            if ($customer) {
                $orderOut['customer'] = $customer->getData();
                $orderOut['customer']['customer_id'] = $orderIn->getCustomerId();
                $orderOut['customer']['primaryBillingAddress'] = $customer->getPrimaryBillingAddress()->getData();
            }
        } else {
            $customer['firstname'] = $orderOut['customer_firstname'];
            $customer['middlename'] = $orderOut['customer_middlename'];
            $customer['lastname'] = $orderOut['customer_lastname'];
            $customer['email'] = $orderOut['customer_email'];
            $orderOut['customer'] = $customer;
        }
        $shipping_address = $orderIn->getShippingAddress();
        if ($shipping_address) {
            $orderOut['shipping_address'] = $shipping_address->getData();
        }

        $billing_address = $orderIn->getBillingAddress();
        if ($billing_address) {
            $orderOut['billing_address'] = $billing_address->getData();
        }

        $payment = $orderIn->getPayment()->getData();
        foreach($payment as $key => $value) {
            if (strpos($key, 'cc_') !== 0) {
                $orderOut['payment'][$key] = $value;
            }
        }

        $session = Mage::getModel('core/session');
        $orderOut['visitor'] = $session->getValidatorData();
        return $orderOut;
    }
}