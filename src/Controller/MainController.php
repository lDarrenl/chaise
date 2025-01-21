<?php

namespace App\Controller;

use App\Entity\chaise;
use App\Repository\chaiseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;


class MainController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(chaiseRepository $chaiseRepository): Response
    {
        $Hchaise = $chaiseRepository->findBy([], ['id' => 'DESC'], 4);

        return $this->render('main/index.html.twig', [
            'chaise' => $chaiseRepository,#
        ]);
    }

    #[Route('/gallery', name: 'gallery')]
    public function gallery(chaiseRepository $chaiseRepository): Response
    {
        $chaises = $chaiseRepository->findAll(); 

        return $this->render('main/gallery.html.twig', [
            'chaises' => $chaises,
        ]);
    }

    #[Route('/collection', name: 'collection')]
    public function collection(Request $request, EntityManagerInterface $entityManager, chaiseRepository $chaiseRepository): Response
    {
        $chaise = new chaise();

        $form = $this->createFormBuilder($chaise)
            ->add('nom', TextType::class, [
                'label' => 'Nom de l\'chaise',
                'attr' => ['class' => 'form-control']
            ])
            ->add('image', FileType::class, [
                'label' => 'Télécharger une image',
                'mapped' => false,
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control']
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Ajouter l\'chaise',
                'attr' => ['class' => 'btn btn-primary mt-3']
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );

                    $chaise->setImage('images/' . $newFilename);
                } catch (FileException $e) {
                }
            }

            $entityManager->persist($chaise);
            $entityManager->flush();

            return $this->redirectToRoute('collection');
        }

        $chaises = $chaiseRepository->findAll();

        return $this->render('main/collection.html.twig', [
            'form' => $form->createView(),
            'chaises' => $chaise,
        ]);
    }

    #[Route('/contact', name: 'contact')]
    public function contact(): Response
    {
        return $this->render('main/contact.html.twig', [
            'title' => 'Nos contact', 
        ]);
    }

    #[Route('/collection/delete/{id}', name: 'delete_chaise', methods: ['POST'])]
    public function delete(int $id, EntityManagerInterface $entityManager, chaiseRepository $hchaiseRepository): Response
    {
        $chaise = $chaiseRepository->find($id);

        if (!$chaise) {
            $this->addFlash('error', 'chaise introuvable');
            return $this->redirectToRoute('collection');
        }

        $entityManager->remove($chaise);
        $entityManager->flush();

        $this->addFlash('success', 'chaise supprimée avec succès');
        return $this->redirectToRoute('collection');
    }
}
