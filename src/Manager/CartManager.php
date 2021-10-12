<?php

namespace App\Manager;

use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\User;
use App\Services\StripeService;
use Doctrine\ORM\EntityManagerInterface;

class CartManager
{   
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var StripeService
     */
    protected $stripeService;

    /**
     * @param EntityManagerInterface $entityManager
     * @param StripeService $stripeService
     */
    public function __construct(EntityManagerInterface $entityManager, StripeService $stripeService)
    {
        $this->em = $entityManager;
        $this->stripeService = $stripeService;
    }

    public function intentSecret(Cart $cart)
    {
        $intent = $this->stripeService->paymentItent($cart);

        return $intent['client_secret'] ?? null;
    }

    public function stripe(array $stripeParameter, Cart $cart)
    {
        $resource = null;
        $data = $this->stripeService->stripe($stripeParameter,$cart);

        if($data){
            $resource = [
                'stripeBrand'=>$data['charges']['data'][0]['payment_method_details']['card']['brand'],
                'stripeLast4'=>$data['charges']['data'][0]['payment_method_details']['card']['last4'],
                'stripeId'=>$data['charges']['data'][0]['id'],
                'stripeStatus'=>$data['charges']['data'][0]['status'],
                'stripeToken'=>$data['client_secret']
            ];
        }

        return $resource;
    }
    
    public function create_subscription(Cart $cart,User $user, $products)
    {
        $order = new Order();
        $prodArray=[];
        foreach($products as $product){
            $ref = ['title'=>$product->getBook()->getTitle(),'price'=>$product->getBook()->getPrice(),'quantity'=>$product->getQuantity()];
            array_push($prodArray,$ref);
        }
        
        
        $order->setUser($user);
        $order->setProducts($prodArray);
        $order->setPrice($cart->getTotal());
        $this->em->persist($order);
        $this->em->flush();

    }
}