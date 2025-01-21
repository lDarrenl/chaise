<?php

namespace App\Controller;

use App\Entity\Horloge;
use App\Repository\HorlogeRepository;
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
    public function index(HorlogeRepository $HorlogeRepository): Response
    {
        $Horloge = $HorlogeRepository->findBy([], ['id' => 'DESC'], 4);

        return $this->render('main/index.html.twig', [
            'Horloge' => $Horloge,
        ]);
    }

    #[Route('/gallery', name: 'gallery')]
    public function gallery(HorlogeRepository $horlogeRepository): Response
    {
        $horloges = $horlogeRepository->findAll(); 

        return $this->render('main/gallery.html.twig', [
            'horloges' => $horloges,
        ]);
    }

    #[Route('/collection', name: 'collection')]
    public function collection(Request $request, EntityManagerInterface $entityManager, HorlogeRepository $horlogeRepository): Response
    {
        $horloge = new Horloge();

        $form = $this->createFormBuilder($horloge)
            ->add('nom', TextType::class, [
                'label' => 'Nom de l\'horloge',
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
                'label' => 'Ajouter l\'horloge',
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

                    $horloge->setImage('images/' . $newFilename);
                } catch (FileException $e) {
                }
            }

            $entityManager->persist($horloge);
            $entityManager->flush();

            return $this->redirectToRoute('collection');
        }

        $horloges = $horlogeRepository->findAll();

        return $this->render('main/collection.html.twig', [
            'form' => $form->createView(),
            'horloges' => $horloges,
        ]);
    }

    #[Route('/contact', name: 'contact')]
    public function contact(): Response
    {
        return $this->render('main/contact.html.twig', [
            'title' => 'Nos contact', 
        ]);
    }

    #[Route('/collection/delete/{id}', name: 'delete_horloge', methods: ['POST'])]
    public function delete(int $id, EntityManagerInterface $entityManager, HorlogeRepository $horlogeRepository): Response
    {
        $horloge = $horlogeRepository->find($id);

        if (!$horloge) {
            $this->addFlash('error', 'Horloge introuvable');
            return $this->redirectToRoute('collection');
        }

        $entityManager->remove($horloge);
        $entityManager->flush();

        $this->addFlash('success', 'Horloge supprimée avec succès');
        return $this->redirectToRoute('collection');
    }
}
