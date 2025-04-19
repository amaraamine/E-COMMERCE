<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/product')]
class ProductController extends AbstractController
{
    #[Route('/', name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('product/index.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }



    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $image-> $form->get('image')->getData();
           
            if ($image) {
                $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$image->guessExtension();
                // Move the file to the directory where images are stored
                try {
                    $image->move(
                        $this->getParameter('image_dir'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $product->setImage('$newFilename');
                    // Handle exception if something happens during file upload
                }

                // Update the product image property to store the image file name
                $product->setImage($newFilename);
            }
            $entityManager->persist($product);
            $entityManager->flush();

            $stockHistory = new AddProductHistory();
            $stockHistory->setQte($product->getStock());
            $stockHistory->setProduct($product);
            $stockHistory->setDate(new \DateTimeImmutable());
            $entityManager->persist($stockHistory);
            $entityManager->flush();
            // Add a flash message to notify the user of success
            
            $this->addFlash(type:'success',message:'votre produit a été ajouté ');


            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addflush(type:'succes',message:'votre produit a été modifié');

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush(type:'danger',message:'votre pruidt a été supprimer');
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }
    
    #[Route('/addStock/{id}', name: 'app_product_stock_add', methods: ['POST'])]
    public function addStock($id , EntityManagerInterface $entityManager, Request $request , ProductRepository): Response
    {
        $addProductHistory = new AddProductHistory();
        $form = $this->createForm(AddProductHistoryType::class, $addStock);
        $form->handleRequest($request);
        
        $product = $productRepository->find($id);

        if($form->isSubmitted() && $form->isValid()){
            if($addStock->getQte() > 0){
                $newQte = $product->getStock() + $addStock->getQte();
                $product->setStock($newQte);

                $entityManager->persist($product);
                $entityManager->flush();
            // Add a flash message to notify the user of success
            
                $this->addFlash(type:'success',message:'le stock de produit a été mis modifié ');
                return $this->redirectToRoute('app_product_index');
            }else{
                $this->addFlash(type:'danger',message:'le stock ne doit pas être inférieur à 0 ');
                return $this->redirectToRoute('app_product_index', ['id'=>$product->getID()],);
            }





            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('product/addStock.html.twig', 
        ['form' => $form->createView(),
        'product' => $product,
        ]);
      
  
  
    }
