<?php

namespace App\Command;
use PDO;

use App\Entity\Faq;
use App\Entity\CampaignConfig;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;


#[AsCommand(name: 'app:create-faq')]
class CreateFAQ extends Command
{
    //protected static $defaultName = 'app:create-faq';
    public function __construct(private EntityManagerInterface $manager, private UserPasswordHasherInterface $passwordEncoder, private ParameterBagInterface $paramBag)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $BURL = $this->paramBag->get('app.base_url');
        

        

        $_FAQDATA_EN = [
            [
                'campaign' => 'cny_2026',
                'category' => 'Redemption: Supermarket, Hypermarket & E-commerce platform',
                'question' => '1. Who is eligible to participate in this promotion?',
                'answer' => 'This promotion is open to all non-Muslims aged 21 years and above who are residents in Malaysia. Employees of the Organiser, its associate agencies, affiliates, distributors, dealers, sponsors, advertising and contest agencies, and members of their immediate families will not be eligible to participate in this promotion.',
                'locale' => 'en',
                'weight' => 1,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Redemption: Supermarket, Hypermarket & E-commerce platform',
                'question' => '2. How can I participate in this promotion and redeem the gifts?',
                'answer' => '<h1 class="my-2">West Malaysia - Supermarket, Hypermarket &amp; Ecommerce platform</h1>
                <table width="100%" class="home-faq-table" border="1">
                <tbody>
                    <tr>
                    <th class="home-faq-header" width="60%">Minimum spend in a single receipt</th>
                    <th class="home-faq-header" width="40%">Get</th>
                    </tr>
                    <tr>
                    <td>3 cartons of any variant of 1664 (320ml or 500ml)</td>
                    <td>1x 1664 Limited Edition Rummy Set</td>
                    </tr>
                    <tr>
                    <td>
                        <p class="my-3">1 carton of Sapporo</p>
                        <p class="my-3">AND</p>
                        <p class="my-3">2 cartons of</p>
                        <content>
                        <ul class="list-disc">
                            <li>Carlsberg Smooth Draught (320ml or 500ml)</li>
                            <li>any variant of Somersby except Shandy &amp; 0.0 </li>
                            <li>Sapporo</li>
                        </ul>
                        </content>
                    </td>
                    <td>1 x Case Valker 20\' Travel Luggage</td>
                    </tr>
                    <tr>
                    <td>3 cartons of Connor\'s (320ml or 500ml)</td>
                    <td>1 x Connor\'s BBQ Grill Set</td>
                    </tr>
                </tbody>
                </table>
                <h1 class="my-2">East Malaysia - Supermarket, Hypermarket &amp; Ecommerce platform</h1>
                <table width="100%" class="home-faq-table" border="1">
                    <tr>
                    <th class="home-faq-header" width="60%">Minimum spend in a single receipt</th>
                    <th class="home-faq-header" width="40%">Get</th>
                    </tr>
                    <tr>
                        <td>3 cartons of any variant of 1664 (320ml or 500ml)</td>
                        <td>1x 1664 Limited Edition Rummy Set</td>
                    </tr>
                    <tr>
                    <td>
                        <p class="my-3">2 cartons of Carlsberg Smooth Draught (320ml or 500ml)</p>
                        <p class="my-3">AND</p>
                        <p class="my-3">1 carton of</p>
                        <content>
                        <ul class="list-disc">
                            <li>Carlsberg Smooth Draught (320ml or 500ml)</li>
                            <li>any variant of Somersby except Shandy &amp; 0.0</li>
                            <li>Sapporo</li>
                        </ul>
                        </content>  
                        
                    </td>
                    <td>1 x Case Valker 20\' Travel Luggage</td>
                    </tr>
                    <tr>
                    <td>3 cartons of Connor\'s (320ml or 500ml)</td>
                        <td>1 x Connor\'s BBQ Grill Set</td>
                    </tr>
                </table>',
                'locale' => 'en',
                'weight' => 2,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Redemption: Supermarket, Hypermarket & E-commerce platform',
                'question' => '3. What are the participating Carlsberg products?',
                'answer' => 'The participating products for this redemption are:<ul><li>Carlsberg Smooth Draught</li><li>1664 any variant</li><li>Somersby any variant (except Shandy & 0.0)</li><li>Sapporo</li><li>Connor\'s</li></ul>',
                'locale' => 'en',
                'weight' => 3,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Redemption: Supermarket, Hypermarket & E-commerce platform',
                'question' => '4. Where can I purchase the redemption gift?',
                'answer' => 'Unfortunately, our redemption gift is not available for sale.',
                'locale' => 'en',
                'weight' => 4,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Redemption: Supermarket, Hypermarket & E-commerce platform',
                'question' => '5. Which are the participating outlets for this promotion?',
                'answer' => '
                    <table class="home-faq-table">
                    <tr>
                    <th class="home-faq-header">Channel</th>
                    <th class="home-faq-header">Participating Outlet</th>
                    </tr>
                    <tr>
                    <td>Supermarkets & Hypermarkets</td>
                    <td>Selected Supermarkets & Hypermarkets with Carlsberg CNY 2026 Point-of-Sale Materials displayed in the premises. </td>
                    </tr>
                    <tr>
                    <td>E-commerce platforms</td>
                    <td>
                        <content>
                        <ul class="list-disc">
                            <li>Carlsberg Official Store on Shopee </li>
                            <li>Carlsberg Official Store (powered by TME) on GRAB</li>
                            <li>Panda Mart on Food Panda</li>
                        </ul>
                        </content>                      
                    </td>
                    </tr>
                    </table>',
                'locale' => 'en',
                'weight' => 5,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Redemption: Supermarket, Hypermarket & E-commerce platform',
                'question' => '6. How do I redeem the redemption gift(s) if I purchase from the Carlsberg Official Store on Shopee?',
                'answer' => 'When purchasing from the Carlsberg Official Store on Shopee, the redemption gift(s) will be delivered together with your order, while stocks last and on a first-come, first-served basis.',
                'locale' => 'en',
                'weight' => 6,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Redemption: Supermarket, Hypermarket & E-commerce platform',
                'question' => '7. For participating outlets, where can I find the receipt number?',
                'answer' => 'You may refer to some of the sample receipts (proofs of purchase) below:<br>
                    <br>
                    <div class="flex flex-col w-full items-center">
                        <div class="faq-receipts">
                            <div>
                                <img src="' . $BURL . 'build/images/FAQ/shm-lotus-en.png" width="100%" alt="" style="max-width: 400px; height: auto;">
                            </div>
                            <div>
                                <img src="' . $BURL . 'build/images/FAQ/shm-aeon-en.png" width="100%" alt="" style="max-width: 400px; height: auto;">
                            </div>
                            <div>                 
                                <img src="' . $BURL . 'build/images/FAQ/shm-jaya-en.png" width="100%" alt="" style="max-width: 400px; height: auto;">
                            </div>
                            <div>
                                <img src="' . $BURL . 'build/images/FAQ/shm-servay-en.png" width="100%" alt="" style="max-width: 400px; height: auto;">
                            </div>
                            <div>
                                <img src="' . $BURL . 'build/images/FAQ/shm-takiong-en.png" width="100%" alt="" style="max-width: 400px; height: auto;">
                            </div>
                            <div>                 
                                <img src="' . $BURL . 'build/images/FAQ/shm-emart-en.png" width="100%" alt="" style="max-width: 400px; height: auto;">
                            </div>                           
                        </div>                        
                    </div>
                ',
                'locale' => 'en',
                'weight' => 7,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Redemption: Supermarket, Hypermarket & E-commerce platform',
                'question' => '8. What information must be visible on the receipt (proof of purchase) for my submission to be valid?',
                'answer' => '
                 <ul>
                    <li>Outlet address</li>
                    <li>Receipt number</li>
                    <li>Name and/or logo of the outlet where the purchase was made</li>
                    <li>Date of purchase</li>
                    <li>Participating products purchased</li>
                    <li>Correct amount/quantity purchased</li>
                </ul>
                <br>
                <div class="flex w-full justify-center">
                    <img src="' . $BURL . 'build/images/FAQ/shm_faq_valid_en.png" width="100%" alt="" style="max-width: 640px; height: auto;">
                </div>
                <p>Please note that several examples of rejected receipts (proofs of purchase) are provided for your reference.</p>
                <BR>
                    <div class="grid" aria="flex flex-col w-full items-center">
                        <div class="faq-receipts" >
                            <div >
                                <img src="' . $BURL . 'build/images/FAQ/shm_invalid_1_en.png" width="100%" alt="" >
                            </div>
                            <div >
                                <img src="' . $BURL . 'build/images/FAQ/shm_invalid_2_en.png" width="100%" alt="" >
                            </div>
                            <div >                 
                                <img src="' . $BURL . 'build/images/FAQ/shm_invalid_3_en.png" width="100%" alt="" >
                            </div>
                            <div >
                                <img src="' . $BURL . 'build/images/FAQ/shm_invalid_4_en.png" width="100%" alt="" >
                            </div>
                            <div >
                                <img src="' . $BURL . 'build/images/FAQ/shm_invalid_5_en.png" width="100%" alt="" >
                            </div>
                            <div >                 
                                <img src="' . $BURL . 'build/images/FAQ/shm_invalid_6_en.png" width="100%" alt="" >
                            </div>
                            <div >
                                <img src="' . $BURL . 'build/images/FAQ/shm_invalid_7_en.png" width="100%" alt="" >
                            </div>
                            <div >
                                <img src="' . $BURL . 'build/images/FAQ/shm_invalid_8_en.png" width="100%" alt="" >
                            </div>
                        </div>                        
                    </div>
                ',
                'locale' => 'en',
                'weight' => 8,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Redemption: Supermarket, Hypermarket & E-commerce platform',
                'question' => '9. Can I combine two receipts to participate in the promotion?',
                'answer' => 'No. Unfortunately, entries can only be made on a SINGLE receipt to be considered as valid for the promotion.',
                'locale' => 'en',
                'weight' => 9,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Redemption: Supermarket, Hypermarket & E-commerce platform',
                'question' => '10. Do I need to keep my receipt?',
                'answer' => 'Yes. You are required to keep the original receipt for verification and redemption purposes.',
                'locale' => 'en',
                'weight' => 10,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Redemption: Supermarket, Hypermarket & E-commerce platform',
                'question' => '11. How do I know if my submission is successful?',
                'answer' => '
                After completing the required fields and uploading your receipt and submitting your form, a pop-up thank-you message will appear. In the meantime, you can check your redemption status here <a href="https://bestwithcarlsberg.my/cny/en/chk_status" class="home-faq-link">https://bestwithcarlsberg.my/cny/en/chk_status</a> by keying in your submitted mobile number.
                ',
                'locale' => 'en',
                'weight' => 11,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Redemption: Supermarket, Hypermarket & E-commerce platform',
                'question' => '12. What should I do if the status of my submission entry remains the same after 7 working days from the date of successful submission?',
                'answer' => '
                You can reach us through our customer support team at <a href="mailto:carlsberg@s360plus.com" class="home-faq-link">carlsberg@s360plus.com</a> or by phone at 1300-22-8899. Our hotline operates Monday to Sunday from 12pm to 9pm (excluding public holidays).  
                ',
                'locale' => 'en',
                'weight' => 12,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Redemption: Supermarket, Hypermarket & E-commerce platform',
                'question' => '13. How do I redeem the redemption gift(s) once my entry is approved?',
                'answer' => 'Once your entry is approved, we will process and deliver your redemption gift(s) to the given delivery address within ninety [90] working days. You may check your redemption status at <a href="https://bestwithcarlsberg.my/cny/en/chk_status" class="home-faq-link">https://bestwithcarlsberg.my/cny/en/chk_status</a> by keying in your submitted mobile number.',
                'locale' => 'en',
                'weight' => 13,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Redemption: Supermarket, Hypermarket & E-commerce platform',
                'question' => '14. How many redemption gift(s) can a participant redeem?',
                'answer' => '
                One [1] unique mobile number and one [1] unique NRIC   may redeem up to a maximum of ONE [1] respective redemption gift, allocated on a first come first served basis while stock lasts.
                ',
                'locale' => 'en',
                'weight' => 14,
            ],
            
            [
                'campaign' => 'cny_2026',
                'category' => 'Redemption: Supermarket, Hypermarket & E-commerce platform',
                'question' => '15. What should I do if I receive a damaged or non-functional redemption gift(s)?',
                'answer' => '
                If you receive a redemption gift(s) that is damaged or not functioning, please contact our support team via email at <a href="mailto:carlsberg@s360plus.com" class="home-faq-link">carlsberg@s360plus.com</a> within three [3]  working days of receiving the item. Kindly provide a clear video or photo of the damaged gift, along with your full name, mobile number, and delivery address for verification purposes. We will review your case and assist with a replacement, subject to the promotion\'s terms and conditions and stock availability.
                ',
                'locale' => 'en',
                'weight' => 15,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Redemption: Supermarket, Hypermarket & E-commerce platform',
                'question' => '16. Who do I contact if I have further queries?',
                'answer' => '
                You can reach us through our customer support team at <a href="mailto:carlsberg@s360plus.com" class="home-faq-link">carlsberg@s360plus.com</a> or by phone at 1300-22-8899. Our hotline operates Monday to Sunday from 12pm to 9pm (excluding public holidays).  
                ',
                'locale' => 'en',
                'weight' => 16,
            ], 
            // 
            // 2nd Category Bar, Café & Restaurant / Coffee Shop & Food Court
            // 
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '1. Who is eligible to participate in this promotion?',
                'answer' => '
                This promotion is open to all non-Muslims aged 21 years and above who are residents in Malaysia. Employees of the Organiser, its associate agencies, affiliates, distributors, dealers, sponsors, advertising and contest agencies, and members of their immediate families will not be eligible to participate in this promotion.
                ',
                'locale' => 'en',
                'weight' => 2,
            ], 
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '2. How can I participate in this promotion to win weekly angpow?',
                'answer' => '
                <content>
                    <table width="100%" class="home-faq-table" border="1">
                        <thead>
                            <tr>
                                <th class="home-faq-header" width="20%">Channel</th>
                                <th class="home-faq-header" width="50%">Every purchase in a single receipt</th>
                                <th class="home-faq-header" width="30%">Get</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td rowspan="2">Bar, Café & Restaurant</td>
                                <td>RM68 worth of Carlsberg Danish Pilsner and/or Carlsberg Smooth Draught</td>
                                <td>1 x contest entry</td>
                            </tr>
                            <tr>
                                <td>RM68 worth of 1664/Connor\'s/Sapporo/Somersby (any variants)</td>
                                <td>2 x contest entries</td>
                            </tr>
                            <tr>
                                <td>Coffee Shop & Food Court</td>
                                <td>
                                    Any 3 big bottles of the following Carlsberg portfolio of products:
                                    <ul style="margin-left: 20px; list-style-type: disc;">
                                        <li>Carlsberg Danish Pilsner (640ml)</li>
                                        <li>Carlsberg Smooth Draught (580ml)</li>
                                        <li>Connor\'s Xtra Malt (640ml)</li>
                                        <li>Sapporo (640ml)</li>
                                    </ul>
                                </td>
                                <td>1 x contest entry</td>
                            </tr>
                        </tbody>
                    </table>
                </content>
                ',
                'locale' => 'en',
                'weight' => 2,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '3. What are the participating Carlsberg products?',
                'answer' => '
                <p class="mb-3">Bar, Café & Restaurant</p>
                <content>
                    <ul>
                        <li>Carlsberg Danish Pilsner</li>
                        <li>Carlsberg Smooth Draught</li>
                        <li>1664</li>
                        <li>Connor\'s</li>
                        <li>Sapporo</li>
                        <li>Somersby (any variants)</li>
                    </ul>
                </content>
                <p class="my-3">Coffee Shop & Food Court</p>
                <content>
                    <ul>
                        <li>Carlsberg Danish Pilsner</li>
                        <li>Carlsberg Smooth Draught</li>
                        <li>Connor\'s</li>
                        <li>Sapporo</li>
                    </ul>
                </content>
                ',
                'locale' => 'en',
                'weight' => 3,
            ], 
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '4. Which are the participating outlets for this promotion? ',
                'answer' => '
                    <table width="100%" class="home-faq-table" border="1">
                    <tr>
                        <th class="home-faq-header" width="40%">Channel</th>
                        <th class="home-faq-header" width="60%">Participating Outlet</th>
                    </tr>
                    <tr>
                        <td>
                            Bar, Café & Restaurant 
                        </td>
                        <td>
                            Selected Bar, Café & Restaurant with CNY 2026 Point-of-Sale Materials. 
                        </td>                        
                    </tr>
                     <tr>
                        <td>
                            Coffee Shop & Food Court 
                        </td>
                        <td>
                            Selected Coffee Shop & Food Court with CNY 2026 Point-of-Sale Materials.
                        </td>                        
                    </tr>
                    </table>
                ',
                'locale' => 'en',
                'weight' => 4,
            ], 
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '5. For participating outlets, where can I find the receipt number?',
                'answer' => '
                You may refer to some of the sample receipts (proofs of purchase) below:<br>
                    <br>
                    <div class="flex flex-col w-full items-center">
                        <div class="faq-receipts">
                            <div>
                                <img src="' . $BURL . 'build/images/FAQ/mont_brew_en.png" width="100%" alt="" style="max-width: 400px; height: auto;">
                            </div>
                            <div>
                                <img src="' . $BURL . 'build/images/FAQ/mont_timeless_en.png" width="100%" alt="" style="max-width: 400px; height: auto;">
                            </div>
                            <div>                 
                                <img src="' . $BURL . 'build/images/FAQ/tont_666_en.png" width="100%" alt="" style="max-width: 400px; height: auto;">
                            </div>
                            <div>
                                <img src="' . $BURL . 'build/images/FAQ/tont_eight_en.png" width="100%" alt="" style="max-width: 400px; height: auto;">
                            </div>                          
                        </div>                        
                    </div>
                ',
                'locale' => 'en',
                'weight' => 5,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '6. What information must be visible on the receipt (proof of purchase) for my entry to be valid?',
                'answer' => 'The receipt (proof of purchase) must clearly display the following details:
                    <content>
                    <ul>
                    <li>Outlet address</li>
                    <li>Receipt number</li>
                    <li>Name and/or logo of the outlet where the purchase was made</li> 
                    <li>Date of purchase</li> 
                    <li>Participating products purchased</li> 
                    <li>Correct amount/quantity purchased</li>
                    </ul>
                    </content>
                    <br>
                    <div class="flex w-full justify-center">
                        <img src="' . $BURL . 'build/images/FAQ/mont_tont_faq_valid_en.png" width="100%" alt="" style="max-width: 640px; height: auto;">
                    </div>
                    <p>Please note that several examples of rejected receipts (proofs of purchase) are provided for your reference.</p>
                    <BR>
                        <div class="grid" aria="flex flex-col w-full items-center">
                            <div class="faq-receipts" >
                                <div >
                                    <img src="' . $BURL . 'build/images/FAQ/mont_tont_invalid_1_en.png" width="100%" alt="" >
                                </div>
                                <div >
                                    <img src="' . $BURL . 'build/images/FAQ/mont_tont_invalid_2_en.png" width="100%" alt="" >
                                </div>
                                <div >                 
                                    <img src="' . $BURL . 'build/images/FAQ/mont_tont_invalid_3_en.png" width="100%" alt="" >
                                </div>
                                <div >
                                    <img src="' . $BURL . 'build/images/FAQ/mont_tont_invalid_4_en.png" width="100%" alt="" >
                                </div>
                                <div >
                                    <img src="' . $BURL . 'build/images/FAQ/mont_tont_invalid_5_en.png" width="100%" alt="" >
                                </div>
                                <div >                 
                                    <img src="' . $BURL . 'build/images/FAQ/mont_tont_invalid_6_en.png" width="100%" alt="" >
                                </div>
                                <div >
                                    <img src="' . $BURL . 'build/images/FAQ/mont_tont_invalid_7_en.png" width="100%" alt="" >
                                </div>
                                <div >
                                    <img src="' . $BURL . 'build/images/FAQ/mont_tont_invalid_8_en.png" width="100%" alt="" >
                                </div>
                            </div>                        
                        </div>
                ',
                'locale' => 'en',
                'weight' => 6,
            ], 
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '7. Can I combine two receipts to participate in the promotion?',
                'answer' => 'No. Unfortunately, entries can only be made on a SINGLE receipt to be considered as valid for the promotion.                    
                ',
                'locale' => 'en',
                'weight' => 7,
            ], 
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '8. Do I need to keep my receipt?',
                'answer' => 'Yes. You are required to keep the original receipt for verification and redemption purposes.',
                'locale' => 'en',
                'weight' => 8,
            ], 
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '9. How do I know if my submission is successful?',
                'answer' => 'After completing the required fields and uploading your receipt, a pop-up thank you message will appear. ',
                'locale' => 'en',
                'weight' => 9,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '10.  Is there a limit to the number of entries per receipt for participation in the contest?',
                'answer' => '
                No, there is no limit. The number of qualifying entries will be determined based on the purchase criteria and will be allocated accordingly, rounded down to the nearest whole number where applicable, as shown in the table below. 
                <br>
                <BR>
                 <table width="100%" class="home-faq-table" border="1">
                     <tr>
                        <th colspan="2" class="home-faq-header">Bar, Café & Restaurant</th>                        
                    </tr>
                    <tr>
                        <td colspan="2">Receipt A </td>
                    </tr>
                    <tr>
                        <td width="40%">Carlsberg Smooth Draught (10 Glass) </td>
                        <td width="60%">RM 150</td>
                    </tr>
                    <tr>
                        <td>Carlsberg Danish Pilsner (1 Bucket 5\'S)</td>
                        <td>RM 100</td>
                    </tr>
                    <tr>
                        <td>Total Qualifying Spend</td>
                        <td>RM 250</td>
                    </tr>
                    <tr>
                        <td>Total Qualifying Entries </td>
                        <td>3</td>
                    </tr>
                </table>
                <BR>
                <table width="100%" class="home-faq-table" border="1">
                     <tr>
                        <th colspan="2" class="home-faq-header">Coffee Shop & Food Court </th>                        
                    </tr>
                    <tr>
                        <td colspan="2">Receipt B </td>
                    </tr>
                    <tr>
                        <td width="40%">Carlsberg Smooth Draught</td>
                        <td width="60%">5 Big Bottles </td>
                    </tr>
                    <tr>
                        <td>Carlsberg Danish Pilsner</td>
                        <td>12 Big Bottles </td>
                    </tr>
                    <tr>
                        <td>Total Amount </td>
                        <td>17 Big Bottles</td>
                    </tr>
                    <tr>
                        <td>Total Qualifying Entries </td>
                        <td>5</td>
                    </tr>
                </table>

                ',
                'locale' => 'en',
                'weight' => 10,
            ], 
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '11. Is there a limit to the number of entries that can be submitted throughout the promotion period?',
                'answer' => 'No, there is no limit to the number of entries that can be submitted for this 
            promotion. You can submit as many entries as you wish by following the 
            guidelines and requirements set out in the terms and conditions or promotional 
            materials. ',
                'locale' => 'en',
                'weight' => 11,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '12. What is the weekly submission period?',
                'answer' => '
                Submission Week<br>
                <table width="100%" class="home-faq-table" border="1">
                    <tr>
                        <th class="home-faq-header" width="15%" class="text-center">Submission Week</th>
                        <th class="home-faq-header" width="45%">Start Date</th>
                        <th class="home-faq-header" width="45%">End Date</th>
                    </tr>
                    <tr>
                        <td class="text-center">1</td>
                        <td>Thursday, 1 January, 2026</td>
                        <td>Friday, 9 January, 2026</td>
                    </tr>
                    <tr>
                        <td class="text-center">2</td>
                        <td>Saturday, 10 January, 2026</td>
                        <td>Friday, 16 January, 2026</td>
                    </tr>
                    <tr>
                        <td class="text-center">3</td>
                        <td>Saturday, 17 January, 2026</td>
                        <td>Friday, 23 January, 2026</td>
                    </tr>
                    <tr>
                        <td class="text-center">4</td>
                        <td>Saturday, 24 January, 2026</td>
                        <td>Friday, 30 January, 2026</td>
                    </tr>
                    <tr>
                        <td class="text-center">5</td>
                        <td>Saturday, 31 January, 2026</td>
                        <td>Friday, 6 February, 2026</td>
                    </tr>
                    <tr>
                        <td class="text-center">6</td>
                        <td>Saturday, 7 February, 2026</td>
                        <td>Friday, 13 February, 2026</td>
                    </tr>
                    <tr>
                        <td class="text-center">7</td>
                        <td>Saturday, 14 February, 2026</td>
                        <td>Friday, 20 February, 2026</td>
                    </tr>
                    <tr>
                        <td class="text-center">8</td>
                        <td>Saturday, 21 February, 2026</td>
                        <td>Saturday, 28 February, 2026</td>
                    </tr>
                </table>
                
                ',
                'locale' => 'en',
                'weight' => 12,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '13. How will I know if I am one of the shortlisted winners for the weekly angpow?',
                'answer' => '
                <p>If you are shortlisted as a winner, you will receive a WhatsApp message from 03
                7890 5046 within five [5] working days after the end of the weekly submission 
                period for winner verification.</p><br>
                <p>Our verification attempts will take place within forty-eight [48] hours as follows:</p>
                <br>
                First twenty-four [24] hours: We will attempt to contact shortlisted winners via WhatsApp (up to three [3] times at different time slots).
                <br><br>
                Following twenty-four [24] hours: If there is no response via WhatsApp, our call centre (03-7890 5046) will attempt to reach you by phone (up to three [3] times). 
                During verification, our team will:
                <br>
                <content>
                <ul> 
                <li>Confirm that you meet the eligibility criteria (aged 21 years and above and non
                Muslim).</li>
                <li>Request a photo identification (IC or passport) as proof of eligibility.</li>
                <li>Ask you to answer a skill-based question. </li><br>
                </ul>
                </content>
                <p>Once eligibility and the skill-based question are successfully completed and 
                verified, you will be confirmed as a winner.</p>
                <p>If you believe you may have missed our contact attempts within the 48-hour 
                window, please reach us at <a href="mailto:carlsberg@s360plus.com" class="home-faq-link">carlsberg@s360plus.com</a> for assistance.</p>',
                'locale' => 'en',
                'weight' => 13,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '14. What if I did not receive WhatsApp message for shortlisted winner - how do I 
                check if I am a shortlisted winner? ',
                'answer' => 'All shortlisted winners will be contacted via WhatsApp from 03-7890 5046 using the mobile number provided during your submission.<br><br>
                Our verification process takes place over forty-eight [48] hours:<br><br>
                <content>
                    <ul>
                    <li>First twenty-four [24] hours: We will attempt to contact shortlisted winners via WhatsApp (up to three [3] times at different time slots).</li> 
                    <li>Following twenty-four [24] hours: If no response is received via WhatsApp, our call centre (03-7890 5046) will attempt to reach you by phone (up to three [3] times).</li>
                    </ul>
                </content>
                <BR>
                If you still have not received any message or call after the full forty-eight [48] hour period, and believe you may have been shortlisted, please contact our team at <a href="mailto:carlsberg@s360plus.com" class="home-faq-link">carlsberg@s360plus.com</a> for further assistance.<BR><BR> 
                Please note that only participants contacted within this forty-eight [48] hour verification period are considered shortlisted winners.
                
                ',
                'locale' => 'en',
                'weight' => 14,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '15. What will happen if I answer the skill-based question incorrectly? ',
                'answer' => 'Failure to answer the question correctly will result in the prize being forfeited by the Organizer.                 
                ',
                'locale' => 'en',
                'weight' => 15,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '16. What happens if I fail to respond, provide my photo ID, or complete the skill
                based question within Forty-Eight [48] hours? ',
                'answer' => '
                Failure to complete the winner verification process within Forty-Eight [48] hours — including failure to <BR>
                <BR>(i) respond to the WhatsApp message or call,  
                <BR>(ii) provide valid photo identification as proof of age, and/or 
                <BR>(iii) correctly answer the skill based question — shall result in the shortlisted participant being deemed to have forfeited his/her opportunity to be confirmed as a winner. 
                <br><br>In such an event, the Organizer reserves the right, at its sole discretion, to select the next eligible participant in accordance with the contest\'s judging or shortlisting process. 
                ',
                'locale' => 'en',
                'weight' => 16,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '17. What happens if I feel unsafe and refuse to provide my photo ID for verification if I\'m shortlisted as a winner? ',
                'answer' => 'Failure to provide a photo ID for verification will result in the prize being forfeited by the Organizer. Please be assured that all personal information will be handled with the utmost confidentiality and will be used solely for verification purposes.',
                'locale' => 'en',
                'weight' => 17,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '18. When will I receive my winning angpow?',
                'answer' => 'Once your win is confirmed, our team will contact you within Ten [10] working days to collect your bank details for a transfer, or your address to arrange a cheque delivery in person from the date of winner announcement.
                ',
                'locale' => 'en',
                'weight' => 18,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '19. What is the total number of contest winners throughout the promotion period? ',
                'answer' => '
                <table width="100%" class="home-faq-table" border="1">
                    <thead>
                        <tr>
                            <th class="home-faq-header" width="25%">Prize Type</th>
                            <th class="home-faq-header" width="35%">Prize Amount</th>
                            <th class="home-faq-header" width="20%">Number of winners per week</th>
                            <th class="home-faq-header" width="20%">Total winner</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Grand Prize</td>
                            <td>RM3,888 ang pow</td>
                            <td style="text-align: center;">8</td>
                            <td style="text-align: center;">64</td>
                        </tr>
                        <tr>
                            <td>First Prize</td>
                            <td>RM178 ang pow</td>
                            <td style="text-align: center;">88</td>
                            <td style="text-align: center;">704</td>
                        </tr>
                    </tbody>
                </table>
                ',
                'locale' => 'en',
                'weight' => 19,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '20.  Who do I contact if I have further queries? ',
                'answer' => 'You can reach us through our customer support team at 
                <a href="mailto:carlsberg@s360plus.com" class="home-faq-link">carlsberg@s360plus.com</a> or by phone at 1300-22-8899. Our hotline operates 
                Monday to Sunday from 12pm to 9pm (excluding public holidays). ',
                'locale' => 'en',
                'weight' => 20,
            ],
            //   #######################################################################
            // 3rd Category Contest: 99 Speedmart
            //   #######################################################################
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '1. Who is eligible to participate in this promotion?',
                'answer' => 'This promotion is open to all non-Muslims aged 21 years and above who are residents in Malaysia. Employees of the Organiser, its associate agencies, affiliates, distributors, dealers, sponsors, advertising and contest agencies, and members of their immediate families will not be eligible to participate in this promotion.',
                'locale' => 'en',
                'weight' => 1,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '2. How can I participate in this promotion to win weekly angpow?',
                'answer' => '
                <table width="100%" class="home-faq-table" border="1">
                <tbody>
                    <tr>
                    <th class="home-faq-header" width="30%">Channel</th>
                    <th class="home-faq-header" width="50%">Minimum spend in a single receipt</th>
                    <th class="home-faq-header" width="20%">Get</th>
                    </tr>
                    <tr>
                    <td>99 Speedmart</td>
                    <td>
                        <p class="my-2">1 can (320ml/500ml) or 1 bottle (325ml/330ml/580ml/640ml) of any of the following participating brands in a single receipt:</p>
                        <content>
                        <ul class="list-disc">
                            <li>Carlsberg Danish Pilsner</li>
                            <li>Carlsberg Smooth Draught</li>
                            <li>Carlsberg Special Brew</li>
                            <li>1664 (any variant)</li>
                            <li>Somersby (any variant)</li>
                            <li>Connor\'s</li>
                            <li>Sapporo</li>
                            <li>SKOL</li>
                            <li>Royal Stout</li>
                        </ul>
                        </content>
                    </td>
                    <td>1 entry</td>
                    </tr>
                </tbody>
                </table>',
                'locale' => 'en',
                'weight' => 2,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '3. What are the participating Carlsberg products?',
                'answer' => '
                <content>
                    <ul>
                        <li>Carlsberg Danish Pilsner</li>
                        <li>Carlsberg Smooth Draught</li>
                        <li>Carlsberg Special Brew</li>
                        <li>1664 (any variant)</li>
                        <li>Somersby (any variant)</li>
                        <li>Connor\'s</li>
                        <li>Sapporo</li>
                        <li>SKOL</li>
                        <li>Royal Stout</li>
                    </ul>
                </content>',
                'locale' => 'en',
                'weight' => 3,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '4. Which are the participating outlets for 99 Speedmart?',
                'answer' => '
                <table width="100%" class="home-faq-table" border="1">
                    <tr>
                    <th class="home-faq-header" width="40%">Channel</th>
                    <th class="home-faq-header" width="60%">Participating Outlet</th>
                    </tr>
                    <tr>
                    <td>99 Speedmart</td>
                    <td>Nationwide Outlets</td>
                    </tr>
                </table>',
                'locale' => 'en',
                'weight' => 4,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '5. For participating outlets in 99 Speedmart, where can I find the receipt number?',
                'answer' => '
                You may refer to the sample receipt (proof of purchase) below:<br>
                <br>
                <div class="flex flex-col w-full items-center">
                    <div class="faq-receipts">
                        <div>
                            <img src="' . $BURL . 'build/images/FAQ/S99_receipt_en.png" width="100%" alt="" style="max-width: 400px; height: auto;">
                        </div>                       
                    </div>                        
                </div>',
                'locale' => 'en',
                'weight' => 5,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '6. What information must be visible on the receipt (proof of purchase) for my entry to be valid?',
                'answer' => '
                The receipt (proof of purchase) must clearly display the following details:
                <content>
                <ul>
                    <li>Outlet address</li>
                    <li>Receipt number</li>
                    <li>Name and/or logo of the outlet where the purchase was made</li>
                    <li>Date of purchase</li>
                    <li>Participating products purchased</li>
                    <li>Correct amount/quantity purchased</li>
                </ul>
                </content>
                <br>
                <div class="flex w-full justify-center">
                    <img src="' . $BURL . 'build/images/FAQ/S99_valid_en.png" width="100%" alt="" style="max-width: 640px; height: auto;">
                </div>
                <p>Please note that several examples of rejected receipts (proofs of purchase) are provided for your reference.</p>
                <BR>
                    <div class="grid" aria="flex flex-col w-full items-center">
                        <div class="faq-receipts" >
                            <div >
                                <img src="' . $BURL . 'build/images/FAQ/S99_invalid_non_participating_product_en.png" width="100%" alt="" >
                            </div>
                            <div >
                                <img src="' . $BURL . 'build/images/FAQ/S99_invalid_missing_outlet_en.png" width="100%" alt="" >
                            </div>
                            <div >                 
                                <img src="' . $BURL . 'build/images/FAQ/S99_invalid_receipt_notclear_en.png" width="100%" alt="" >
                            </div>
                            <div >
                                <img src="' . $BURL . 'build/images/FAQ/S99_invalid_non_participating_product_en.png" width="100%" alt="" >
                            </div>
                            <div >
                                <img src="' . $BURL . 'build/images/FAQ/S99_invalid_missing_receipt_number_en.png" width="100%" alt="" >
                            </div>
                            <div >                 
                                <img src="' . $BURL . 'build/images/FAQ/S99_invalid_outside_promo_period_en.png" width="100%" alt="" >
                            </div>
                            <div >
                                <img src="' . $BURL . 'build/images/FAQ/S99_invalid_duplicate_receipt_en.png" width="100%" alt="" >
                            </div>
                            <div >
                                <img src="' . $BURL . 'build/images/FAQ/S99_invalid_missing_outletaddress_en.png" width="100%" alt="" >
                            </div>
                        </div>                        
                    </div>',
                'locale' => 'en',
                'weight' => 6,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '7. Can I combine two receipts to participate in the promotion?',
                'answer' => 'No. Unfortunately, entries can only be made on a SINGLE receipt to be considered as valid for the promotion.',
                'locale' => 'en',
                'weight' => 7,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '8. Do I need to keep my receipt?',
                'answer' => 'Yes. You are required to keep the original receipt for verification and redemption purposes.',
                'locale' => 'en',
                'weight' => 8,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '9. How do I know if my submission is successful?',
                'answer' => 'After completing the required fields and uploading your receipt, a pop-up thank-you message will appear.',
                'locale' => 'en',
                'weight' => 9,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '10. Is there a limit to the number of entries per receipt for participation in the contest?',
                'answer' => 'Each eligible receipt qualifies for one [1] contest entry, regardless of the number of participating cans purchased, as long as the receipt reflects a minimum purchase of one [1] participating product in a single transaction during the promotion period.
                <br><br>
                There is no limit to the number of different receipts you may submit throughout the campaign period. Each valid receipt that meets the qualifying criteria will be accepted as one [1] entry.',
                'locale' => 'en',
                'weight' => 10,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '11. Is there a limit to the number of entries that can be submitted throughout the promotion period?',
                'answer' => 'No, there is no limit to the number of entries that can be submitted for this promotion. You can submit as many entries as you wish by following the guidelines and requirements set out in the terms and conditions or promotional materials.',
                'locale' => 'en',
                'weight' => 11,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '12. What is the weekly submission period?',
                'answer' => '
                <table width="100%" class="home-faq-table" border="1">
                    <tr>
                    <th class="home-faq-header" width="15%" class="text-center">Submission Week</th>
                    <th class="home-faq-header" width="42%">Start Date</th>
                    <th class="home-faq-header" width="43%">End Date</th>
                    </tr>
                    <tr>
                    <td class="text-center">1</td>
                    <td>Thursday, 1 January 2026</td>
                    <td>Friday, 9 January 2026</td>
                    </tr>
                    <tr>
                    <td class="text-center">2</td>
                    <td>Saturday, 10 January 2026</td>
                    <td>Friday, 16 January 2026</td>
                    </tr>
                    <tr>
                    <td class="text-center">3</td>
                    <td>Saturday, 17 January 2026</td>
                    <td>Friday, 23 January 2026</td>
                    </tr>
                    <tr>
                    <td class="text-center">4</td>
                    <td>Saturday, 24 January 2026</td>
                    <td>Friday, 30 January 2026</td>
                    </tr>
                    <tr>
                    <td class="text-center">5</td>
                    <td>Saturday, 31 January 2026</td>
                    <td>Friday, 6 February 2026</td>
                    </tr>
                    <tr>
                    <td class="text-center">6</td>
                    <td>Saturday, 7 February 2026</td>
                    <td>Friday, 13 February 2026</td>
                    </tr>
                    <tr>
                    <td class="text-center">7</td>
                    <td>Saturday, 14 February 2026</td>
                    <td>Friday, 20 February 2026</td>
                    </tr>
                    <tr>
                    <td class="text-center">8</td>
                    <td>Saturday, 21 February 2026</td>
                    <td>Saturday, 28 February 2026</td>
                    </tr>
                </table>',
                'locale' => 'en',
                'weight' => 12,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '13. How will I know if I am one of the shortlisted winners for the weekly angpow?',
                'answer' => '
                If you are shortlisted as a winner, you will receive a WhatsApp message from 03-7890 5046 within five [5] working days after the end of the weekly submission period for winner verification.
                <br><br>
                Our verification attempts will take place within forty-eight [48] hours as follows:
                <br>
                <content>
                <ul class="list-disc">
                    <li>First twenty-four [24] hours: We will attempt to contact shortlisted winners via WhatsApp (up to three [3] times at different time slots).</li>
                    <li>Following twenty-four [24] hours: If there is no response via WhatsApp, our call centre (03-7890 5046) will attempt to reach you by phone (up to three [3] times).</li>
                </ul>
                </content>
                <br>
                During verification, our team will:
                <content>
                <ul class="list-disc">
                    <li>Confirm that you meet the eligibility criteria (aged 21 years and above and non-Muslim).</li>
                    <li>Request a photo identification (IC or passport) as proof of eligibility.</li>
                    <li>Ask you to answer a skill-based question.</li>
                </ul>
                </content>
                <br>
                Once eligibility and the skill-based question are successfully completed and verified, you will be confirmed as a winner.
                <br>
                If you believe you may have missed our contact attempts within the 48-hour window, please reach us at <a href="mailto:carlsberg@s360plus.com" class="home-faq-link">carlsberg@s360plus.com</a> for assistance.',
                'locale' => 'en',
                'weight' => 13,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '14. What if I did not receive WhatsApp message for shortlisted winner - how do I check if I am a shortlisted winner?',
                'answer' => '
                All shortlisted winners will be contacted via WhatsApp from 03-7890 5046 using the mobile number provided during your submission.
                <br>
                Our verification process takes place over forty-eight [48] hours:
                <br>
                <content>
                <ul class="list-disc">
                    <li>First twenty-four [24] hours: We will attempt to contact shortlisted winners via WhatsApp (up to three [3] times at different time slots).</li>
                    <li>Following twenty-four [24] hours: If no response is received via WhatsApp, our call centre (03-7890 5046) will attempt to reach you by phone (up to three [3] times).</li>
                </ul>
                </content>
                <br>
                If you still have not received any message or call after the full forty-eight [48] hour period, and believe you may have been shortlisted, please contact our team at <a href="mailto:carlsberg@s360plus.com" class="home-faq-link">carlsberg@s360plus.com</a> for further assistance.
                <br><br>
                Please note that only participants contacted within this forty-eight [48] hour verification period are considered shortlisted winners.',
                'locale' => 'en',
                'weight' => 14,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '15. What will happen if I answer the skill-based question incorrectly?',
                'answer' => 'Failure to answer the question correctly will result in the prize being forfeited by the Organizer.',
                'locale' => 'en',
                'weight' => 15,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '16. What happens if I fail to respond, provide my photo ID, or complete the skill-based question within Forty-Eight [48] hours?',
                'answer' => '
                Failure to complete the winner verification process within Forty-Eight [48] hours — including failure to <BR>
                <BR>(i) respond to the WhatsApp message or call,  
                <BR>(ii) provide valid photo identification as proof of age, and/or 
                <BR>(iii) correctly answer the skill-based question — shall result in the shortlisted participant being deemed to have forfeited his/her opportunity to be confirmed as a winner. 
                <br><br>In such an event, the Organizer reserves the right, at its sole discretion, to select the next eligible participant in accordance with the contest\'s judging or shortlisting process. 
                ',
                'locale' => 'en',
                'weight' => 16,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '17. What happens if I feel unsafe and refuse to provide my photo ID for verification if I\'m shortlisted as a winner?',
                'answer' => 'Failure to provide a photo ID for verification will result in the prize being forfeited by the Organizer. Please be assured that all personal information will be handled with the utmost confidentiality and will be used solely for verification purposes.',
                'locale' => 'en',
                'weight' => 17,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '18. When will I receive my winning angpow?',
                'answer' => 'Once your win is confirmed, our team will contact you within thirty [30] working days to collect your bank details for a transfer, or your address to arrange a cheque delivery in person from the date of winner announcement.',
                'locale' => 'en',
                'weight' => 18,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '19. When will I receive my winning e-Wallet Credit?',
                'answer' => 'Once your win is confirmed, our team will notify you via WhatsApp within thirty [30] working days from the date of the winner announcement to arrange the crediting of the prize into your Touch \'n Go e-Wallet account.',
                'locale' => 'en',
                'weight' => 19,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '20. What is the total number of contest winners throughout the promotion period?',
                'answer' => '
                <table width="100%" class="home-faq-table" border="1">
                    <tr>
                    <th class="home-faq-header" width="30%">Prize Type</th>
                    <th class="home-faq-header" width="30%">Prize Amount</th>
                    <th class="home-faq-header" width="20%">Number of winners per week</th>
                    <th class="home-faq-header" width="20%">Total winner</th>
                    </tr>
                    <tr>
                    <td>Grand Prize</td>
                    <td>RM1,788 angpow</td>
                    <td>11</td>
                    <td>88</td>
                    </tr>
                    <tr>
                    <td>First Prize</td>
                    <td>RM99 e-Wallet Credit</td>
                    <td>168</td>
                    <td>1,344</td>
                    </tr>
                </table>',
                'locale' => 'en',
                'weight' => 20,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '21. How many weekly angpow can a participant win in contest?',
                'answer' => 'One [1] unique mobile number and one [1] unique NRIC can win One [1] Grand Prize OR One [1] First prize throughout the promotion period.',
                'locale' => 'en',
                'weight' => 21,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '22. Who do I contact if I have further queries?',
                'answer' => 'You can reach us through our customer support team at <a href="mailto:carlsberg@s360plus.com" class="home-faq-link">carlsberg@s360plus.com</a> or by phone at 1300-22-8899. Our hotline operates Monday to Sunday from 12pm to 9pm (excluding public holidays).',
                'locale' => 'en',
                'weight' => 22,
            ],
            // 
            // 4th Category Contest: Convenience Store & Mini Market
            // 
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '1. Who is eligible to participate in this promotion?',
                'answer' => 'This promotion is open to all non-Muslims aged 21 years and above who are residents in Malaysia. Employees of the Organiser, its associate agencies, affiliates, distributors, dealers, sponsors, advertising and contest agencies, and members of their immediate families will not be eligible to participate in this promotion.',
                'locale' => 'en',
                'weight' => 1,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '2. How can I participate in this promotion to redeem the Touch \'n Go eWallet credit and a chance to win One [1] iPhone 17 Pro?',
                'answer' => '
                <table width="100%" class="home-faq-table" border="1">
                <tbody>
                    <tr>
                    <th class="home-faq-header" width="30%">Channel</th>
                    <th class="home-faq-header" width="50%">Every purchase in a single receipt</th>
                    <th class="home-faq-header" width="20%">Get</th>
                    </tr>
                    <tr>
                    <td>Convenience Stores & Mini Markets</td>
                    <td>
                        <p class="my-2">RM20 of Carlsberg portfolio of products:</p>
                        <content>
                        <ul class="list-disc">
                            <li>Carlsberg Danish Pilsner</li>
                            <li>Carlsberg Smooth Draught</li>
                            <li>Carlsberg Special Brew</li>
                            <li>1664 any variant</li>
                            <li>Somersby any variant</li>
                            <li>Connor\'s</li>
                            <li>Sapporo</li>
                            <li>SKOL</li>
                            <li>Royal Stout</li>
                        </ul>
                        </content>
                    </td>
                    <td>1 contest entry & RM5 Touch \'n Go eWallet credit</td>
                    </tr>
                </tbody>
                </table>',
                'locale' => 'en',
                'weight' => 2,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '3. What are the participating Carlsberg products?',
                'answer' => '
                <content>
                    <ul>
                        <li>Carlsberg Danish Pilsner</li>
                        <li>Carlsberg Smooth Draught</li>
                        <li>Carlsberg Special Brew</li>
                        <li>1664 any variant</li>
                        <li>Somersby any variant</li>
                        <li>Connor\'s</li>
                        <li>Sapporo</li>
                        <li>SKOL</li>
                        <li>Royal Stout</li>
                    </ul>
                </content>',
                'locale' => 'en',
                'weight' => 3,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '4. Which are the participating outlets for Convenience Stores & Mini Markets?',
                'answer' => '
                <table width="100%" class="home-faq-table" border="1">
                    <tr>
                    <th class="home-faq-header" width="40%">Channel</th>
                    <th class="home-faq-header" width="60%">Participating Outlet</th>
                    </tr>
                    <tr>
                    <td>Convenience Stores & Mini Markets</td>
                    <td>Selected Convenience Stores & Mini Markets with CNY 2026 Point-of-Sale Materials displayed in the premises.</td>
                    </tr>
                </table>',
                'locale' => 'en',
                'weight' => 4,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '5. For participating outlets in Convenience Stores & Mini Markets, where can I find the receipt number?',
                'answer' => '
                You may refer to some of the sample receipts (proofs of purchase) below:<br>
                <br>
                <div class="flex flex-col w-full items-center">
                    <div class="faq-receipts">
                        <div>
                            <img src="' . $BURL . 'build/images/FAQ/CVS_7e_en.png" width="100%" alt="" style="max-width: 400px; height: auto;">
                        </div>                       
                        <div>
                            <img src="' . $BURL . 'build/images/FAQ/CVS_mix_store_en.png" width="100%" alt="" style="max-width: 400px; height: auto;">
                        </div>                       
                        <div>
                            <img src="' . $BURL . 'build/images/FAQ/CVS_orange_cvs_en.png" width="100%" alt="" style="max-width: 400px; height: auto;">
                        </div>                       
                    </div>                        
                </div>',
                'locale' => 'en',
                'weight' => 5,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '6. What information must be visible on the receipt (proof of purchase) for my entry to be valid?',
                'answer' => '
                The receipt (proof of purchase) must clearly display the following details:
                <content>
                <ul>
                    <li>Outlet address</li>
                    <li>Receipt number</li>
                    <li>Name and/or logo of the outlet where the purchase was made</li>
                    <li>Date of purchase</li>
                    <li>Participating products purchased</li>
                    <li>Correct amount/quantity purchased</li>
                </ul>
                </content>
                <br>
                <div class="flex w-full justify-center">
                    <img src="' . $BURL . 'build/images/FAQ/CVS_valid_en.png" width="100%" alt="" style="max-width: 640px; height: auto;">
                </div>
                <p>Please note that several examples of rejected receipts (proofs of purchase) are provided for your reference.</p>
                <BR>
                <div class="grid" aria="flex flex-col w-full items-center">
                    <div class="faq-receipts" >
                        <div >
                            <img src="' . $BURL . 'build/images/FAQ/CVS_invalid_non_participating_product_en.png" width="100%" alt="" >
                        </div>
                        <div >
                            <img src="' . $BURL . 'build/images/FAQ/CVS_invalid_missing_outlet_en.png" width="100%" alt="" >
                        </div>
                        <div >                 
                            <img src="' . $BURL . 'build/images/FAQ/CVS_invalid_receipt_notclear_en.png" width="100%" alt="" >
                        </div>
                        <div >
                            <img src="' . $BURL . 'build/images/FAQ/CVS_invalid_non_participating_product_en.png" width="100%" alt="" >
                        </div>
                        <div >
                            <img src="' . $BURL . 'build/images/FAQ/CVS_invalid_missing_receipt_number_en.png" width="100%" alt="" >
                        </div>
                        <div >                 
                            <img src="' . $BURL . 'build/images/FAQ/CVS_invalid_outside_promo_period_en.png" width="100%" alt="" >
                        </div>
                        <div >
                            <img src="' . $BURL . 'build/images/FAQ/CVS_invalid_duplicate_receipt_en.png" width="100%" alt="" >
                        </div>
                        <div >
                            <img src="' . $BURL . 'build/images/FAQ/CVS_invalid_missing_outletaddress_en.png" width="100%" alt="" >
                        </div>
                    </div>                        
                </div>',
                'locale' => 'en',
                'weight' => 6,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '7. Can I combine two receipts to participate in the promotion?',
                'answer' => 'No. Unfortunately, entries can only be made on a SINGLE receipt to be considered as valid for the promotion.',
                'locale' => 'en',
                'weight' => 7,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '8. Do I need to keep my receipt?',
                'answer' => 'Yes. You are required to keep the original receipt for verification and redemption purposes.',
                'locale' => 'en',
                'weight' => 8,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '9. How do I know if my submission is successful?',
                'answer' => 'After completing the required fields and uploading your receipt, a pop-up thank-you message will appear.',
                'locale' => 'en',
                'weight' => 9,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '10. Is there a limit to the number of entries per receipt for participation in the contest?',
                'answer' => '
                No, there is no limit. The number of qualifying entries will be determined based on the purchase criteria and will be allocated accordingly, as illustrated in the table below.
                <br><br>
                <table width="100%" class="home-faq-table" border="1">
                    <tr>
                        <th colspan="2" class="home-faq-header">Receipt A</th>
                    </tr>
                    <tr>
                        <td width="60%">Carlsberg Smooth Draught (500ml)</td>
                        <td width="40%">RM15</td>
                    </tr>
                    <tr>
                        <td>Sapporo</td>
                        <td>RM20</td>
                    </tr>
                    <tr>
                        <td>Total Qualifying Spend</td>
                        <td>RM35</td>
                    </tr>
                    <tr>
                        <td>Total Qualifying Entries</td>
                        <td>1</td>
                    </tr>
                </table>
                <br>
                <table width="100%" class="home-faq-table" border="1">
                    <tr>
                        <th colspan="2" class="home-faq-header">Receipt B</th>
                    </tr>
                    <tr>
                        <td width="60%">Carlsberg Danish Pilsner (320ml)</td>
                        <td width="40%">RM100</td>
                    </tr>
                    <tr>
                        <td>Somersby</td>
                        <td>RM20</td>
                    </tr>
                    <tr>
                        <td>Total Qualifying Spend</td>
                        <td>RM120</td>
                    </tr>
                    <tr>
                        <td>Total Qualifying Entries</td>
                        <td>6</td>
                    </tr>
                </table>
                <br>
                <table width="100%" class="home-faq-table" border="1">
                    <tr>
                        <th colspan="2" class="home-faq-header">Receipt C</th>
                    </tr>
                    <tr>
                        <td width="60%">1664 Rose</td>
                        <td width="40%">RM100</td>
                    </tr>
                    <tr>
                        <td>Connor\'s</td>
                        <td>RM100</td>
                    </tr>
                    <tr>
                        <td>Total Qualifying Spend</td>
                        <td>RM200</td>
                    </tr>
                    <tr>
                        <td>Total Qualifying Entries</td>
                        <td>10</td>
                    </tr>
                </table>
                <br>
                <table width="100%" class="home-faq-table" border="1">
                    <tr>
                        <th colspan="2" class="home-faq-header">Receipt D</th>
                    </tr>
                    <tr>
                        <td width="60%">Carlsberg Smooth Draught (320ml)</td>
                        <td width="40%">RM10</td>
                    </tr>
                    <tr>
                        <td>Total Qualifying Spend</td>
                        <td>RM10</td>
                    </tr>
                    <tr>
                        <td>Total Qualifying Entries</td>
                        <td>0</td>
                    </tr>
                </table>',
                'locale' => 'en',
                'weight' => 10,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '11. Is there a limit to the number of entries that can be submitted throughout the promotion period?',
                'answer' => 'No, there is no limit to the number of entries that can be submitted for this promotion. You can submit as many entries as you wish by following the guidelines and requirements set out in the terms and conditions or promotional materials.',
                'locale' => 'en',
                'weight' => 11,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '12. How many Touch \'n Go eWallet credit can a participant redeem?',
                'answer' => 'One [1] unique mobile number and one [1] unique NRIC may redeem up to a maximum of FIVE [5] Touch \'n Go eWallet credit, allocated on a first come first served basis while stock lasts.',
                'locale' => 'en',
                'weight' => 12,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '13. How will I know if I am one of the shortlisted winners for the iPhone 17 Pro?',
                'answer' => '
                If you are shortlisted as a winner, you will receive a WhatsApp message from 03-7890 5046 within thirty [30] working days after the end of the promotion period for winner verification.
                <br><br>
                Our verification attempts will take place within forty-eight [48] hours as follows:
                <br>
                <content>
                <ul class="list-disc">
                    <li>First twenty-four [24] hours: We will attempt to contact shortlisted winners via WhatsApp (up to three [3] times at different time slots).</li>
                    <li>Following twenty-four [24] hours: If there is no response via WhatsApp, our call centre (03-7890 5046) will attempt to reach you by phone (up to three [3] times).</li>
                </ul>
                </content>
                <br>
                During verification, our team will:
                <content>
                <ul class="list-disc">
                    <li>Confirm that you meet the eligibility criteria (aged 21 years and above and non-Muslim).</li>
                    <li>Request a photo identification (IC or passport) as proof of eligibility.</li>
                    <li>Ask you to answer a skill-based question.</li>
                </ul>
                </content>
                <br>
                Once eligibility and the skill-based question are successfully completed and verified, you will be confirmed as a winner.
                <br>
                If you believe you may have missed our contact attempts within the 48-hour window, please reach us at <a href="mailto:carlsberg@s360plus.com" class="home-faq-link">carlsberg@s360plus.com</a> for assistance.',
                'locale' => 'en',
                'weight' => 13,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '14. What if I did not receive WhatsApp message for shortlisted winner - how do I check if I am a shortlisted winner?',
                'answer' => '
                All shortlisted winners will be contacted via WhatsApp from 03-7890 5046 using the mobile number provided during your submission.
                <br>
                Our verification process takes place over forty-eight [48] hours:
                <br>
                <content>
                <ul class="list-disc">
                    <li>First twenty-four [24] hours: We will attempt to contact shortlisted winners via WhatsApp (up to three [3] times at different time slots).</li>
                    <li>Following twenty-four [24] hours: If no response is received via WhatsApp, our call centre (03-7890 5046) will attempt to reach you by phone (up to three [3] times).</li>
                </ul>
                </content>
                <br>
                If you still have not received any message or call after the full forty-eight [48] hour period, and believe you may have been shortlisted, please contact our team at <a href="mailto:carlsberg@s360plus.com" class="home-faq-link">carlsberg@s360plus.com</a> for further assistance.
                <br><br>
                Please note that only participants contacted within this forty-eight [48] hour verification period are considered shortlisted winners.',
                'locale' => 'en',
                'weight' => 14,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '15. What will happen if I answer the skill-based question incorrectly?',
                'answer' => 'Failure to answer the question correctly will result in the prize being forfeited by the Organizer.',
                'locale' => 'en',
                'weight' => 15,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '16. What happens if I fail to respond, provide my photo ID, or complete the skill-based question within Forty-Eight [48] hours?',
                'answer' => '
                Failure to complete the winner verification process within Forty-Eight [48] hours — including failure to <BR>
                <BR>(i) respond to the WhatsApp message or call,  
                <BR>(ii) provide valid photo identification as proof of age, and/or 
                <BR>(iii) correctly answer the skill-based question — shall result in the shortlisted participant being deemed to have forfeited his/her opportunity to be confirmed as a winner. 
                <br><br>In such an event, the Organizer reserves the right, at its sole discretion, to select the next eligible participant in accordance with the contest\'s judging or shortlisting process. 
                ',
                'locale' => 'en',
                'weight' => 16,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '17. What happens if I feel unsafe and refuse to provide my photo ID for verification if I\'m shortlisted as a winner?',
                'answer' => 'Failure to provide a photo ID for verification will result in the prize being forfeited by the Organizer. Please be assured that all personal information will be handled with the utmost confidentiality and will be used solely for verification purposes.',
                'locale' => 'en',
                'weight' => 17,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '18. When will I receive my winning iPhone 17 Pro?',
                'answer' => 'Once the Winners are confirmed, delivery of prizes will be completed within sixty [60] working days from the date of the official winner announcement.',
                'locale' => 'en',
                'weight' => 18,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '19. What is the total number of iPhone 17 Pro contest winners throughout the promotion period?',
                'answer' => 'There will be a total of Eight [8] iPhone 17 Pro winners selected during the promotion period.',
                'locale' => 'en',
                'weight' => 19,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '20. How many iPhone 17 Pro can a participant win in contest?',
                'answer' => 'One [1] unique mobile number and one [1] unique NRIC can win One [1] iPhone 17 Pro only.',
                'locale' => 'en',
                'weight' => 20,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '21. If I have redeemed the RM5 Touch \'n Go eWallet credit, am I still eligible to participate in the contest to win an iPhone 17 Pro?',
                'answer' => 'Yes. One [1] unique mobile number and one [1] unique NRIC can win One [1] iPhone 17 Pro and redeem up to a maximum of Five [5] Touch \'n Go eWallet credit.',
                'locale' => 'en',
                'weight' => 21,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '22. Can I request to change the model or color of the iPhone 17 Pro?',
                'answer' => 'No. The model and color of the iPhone 17 is fixed and cannot be changed.',
                'locale' => 'en',
                'weight' => 22,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '23. Who do I contact if I have further queries?',
                'answer' => 'You can reach us through our customer support team at <a href="mailto:carlsberg@s360plus.com" class="home-faq-link">carlsberg@s360plus.com</a> or by phone at 1300-22-8899. Our hotline operates Monday to Sunday from 12pm to 9pm (excluding public holidays).',
                'locale' => 'en',
                'weight' => 23,
            ],
        ];

        $_FAQDATA_CH = [
            [
                'campaign' => 'cny_2026',
                'category' => 'Redemption: Supermarket, Hypermarket & E-commerce platform',
                'question' => '1. 谁具备资格参与本次促销活动？',
                'answer' => '本次促销活动公开予年满21岁或以上的非穆斯林马来西亚居民参与。主办方的员工、其关联机构、附属公司、经销商、代理商、赞助商、广告及促销代理机构的员工，以及其直系亲属，均不符合资格参与本次促销活动。',
                'locale' => 'ch',
                'weight' => 1,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Redemption: Supermarket, Hypermarket & E-commerce platform',
                'question' => '2. 我该如何参与本次促销活动并领取该礼品？',
                'answer' => '<h1 class="my-2">西马 - 超级市场，大型超市 & 电商</h1>
                <table width="100%" class="home-faq-table" border="1">
                <tbody>
                    <tr>
                    <th class="home-faq-header" width="60%">单张收据最低消费</th>
                    <th class="home-faq-header" width="40%">领取</th>
                    </tr>
                    <tr>
                    <td>任意 3 箱 1664 啤酒（320毫升 或 500毫升）</td>
                    <td>1x 限量版1664拉米 </td>
                    </tr>
                    <tr>
                    <td>
                        <p class="my-3">1箱 Sapporo 啤酒</p>
                        <p class="my-3">和 </p>
                        <p class="my-3">任意2箱 </p>
                        <content>
                        <ul class="list-disc">
                            <li>Carlsberg Smooth Draught 啤酒（320毫升 或 500毫升）</li>
                            <li>任意 Somersby 苹果酒（不包括 Shandy 及 0.0）</li>
                            <li>Sapporo 啤酒</li>
                        </ul>
                        </content>
                    </td>
                    <td>1 x Case Valker 20 寸行李箱</td>
                    </tr>
                    <tr>
                    <td>3箱 Connor\'s 黑啤 （320毫升 或 500毫升）</td>
                    <td>1 x Connor\'s 迷你烧烤架</td>
                    </tr>
                </tbody>
                </table>
                <h1 class="my-2">东马 - 超级市场，大型超市 & 电商</h1>
                <table width="100%" class="home-faq-table" border="1">
                    <tr>
                    <th class="home-faq-header" width="60%">单张收据最低消费</th>
                    <th class="home-faq-header" width="40%">领取</th>
                    </tr>
                    <tr>
                        <td>任意 3 箱 1664 啤酒（320毫升 或 500毫升）</td>
                        <td>1x 限量版1664拉米</td>
                    </tr>
                    <tr>
                    <td>
                        <p class="my-3">2箱 Carlsberg Smooth Draught 啤酒（320毫升 或 500毫升）</p>
                        <p class="my-3">和 </p>
                        <p class="my-3">任意1箱</p>
                        <content>
                        <ul class="list-disc">
                            <li>Carlsberg Smooth Draught 啤酒（320毫升 或 500毫升） </li>
                            <li>任意 Somersby 苹果酒（不包括 Shandy 及 0.0）</li>
                            <li>Sapporo 啤酒</li>
                        </ul>
                        </content>  
                        
                    </td>
                    <td>1 x Case Valker 20 寸行李箱</td>
                    </tr>
                    <tr>
                        <td>3箱 Connor\'s 黑啤 （320毫升 或 500毫升）</td>
                        <td>1 x Connor\'s 迷你烧烤架</td>
                    </tr>
                </table>',
                'locale' => 'ch',
                'weight' => 2,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Redemption: Supermarket, Hypermarket & E-commerce platform',
                'question' => '3. 参与本次促销活动的 Carlsberg 产品有哪些？',
                'answer' => '
                    <ul>
                    <li>Carlsberg Smooth Draught 啤酒</li>
                    <li>任意 1664 啤酒</li>
                    <li>任意 Somersby 苹果酒（不包括 Shandy 及 0.0)</li>
                    <li>Sapporo 啤酒</li>
                    <li>Connor\'s 黑啤</li>
                    </ul>',
                'locale' => 'ch',
                'weight' => 3,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Redemption: Supermarket, Hypermarket & E-commerce platform',
                'question' => '4. 我可以在哪里购买该兑换的礼品？',
                'answer' => '很抱歉，我们的兑换礼品不对外出售。',
                'locale' => 'ch',
                'weight' => 4,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Redemption: Supermarket, Hypermarket & E-commerce platform',
                'question' => '5. 哪些门店参与本次促销活动？',
                'answer' => '
                    <table class="home-faq-table">
                    <tr>
                    <th class="home-faq-header">渠道</th>
                    <th class="home-faq-header">参与门店</th>
                    </tr>
                    <tr>
                    <td>超级市场 & 大型超市</td>
                    <td>指定超级市场与大型超市，且其店内陈列有展示Carlsberg CNY 2026 的宣传物料。</td>
                    </tr>
                    <tr>
                    <td>电商平台</td>
                    <td>
                        <content>
                        <ul class="list-disc">
                            <li>Shopee 的 Carlsberg 官方商店 </li>
                            <li>GRAB的 Carlsberg 官方商店（由 TME 授权运营）</li>
                            <li>foodpanda 的 pandamart</li>
                        </ul>
                        </content>                      
                    </td>
                    </tr>
                    </table>',
                'locale' => 'ch',
                'weight' => 5,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Redemption: Supermarket, Hypermarket & E-commerce platform',
                'question' => '6. 若我通过 Shopee 的 Carlsberg 官方商店购买，要如何领取该礼品？',
                'answer' => '
                当您在 Shopee 的 Carlsberg 官方商店购买时，该礼品将与您的订单一同寄送。礼品数量有限，先到先得，送完即止。
                ',
                'locale' => 'ch',
                'weight' => 6,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Redemption: Supermarket, Hypermarket & E-commerce platform',
                'question' => '7. 在参与门店购买时，我可以在哪里找到收据号码？',
                'answer' => '以下提供购买收据示例供您参考:<br>
                    <br>
                    <div class="flex flex-col w-full items-center">
                        <div class="flex flex-col md:flex-row w-full justify-center gap-2 mb-2" style="max-width: 1200px;">
                            <div>
                                <img src="' . $BURL . 'build/images/FAQ/shm-lotus-ch.png" width="100%" alt="" style="max-width: 400px; height: auto;">
                            </div>
                            <div>
                                <img src="' . $BURL . 'build/images/FAQ/shm-aeon-ch.png" width="100%" alt="" style="max-width: 400px; height: auto;">
                            </div>
                            <div>                 
                                <img src="' . $BURL . 'build/images/FAQ/shm-jaya-ch.png" width="100%" alt="" style="max-width: 400px; height: auto;">
                            </div>
                            <div>
                                <img src="' . $BURL . 'build/images/FAQ/shm-servay-ch.png" width="100%" alt="" style="max-width: 400px; height: auto;">
                            </div>
                            <div>
                                <img src="' . $BURL . 'build/images/FAQ/shm-takiong-ch.png" width="100%" alt="" style="max-width: 400px; height: auto;">
                            </div>
                            <div>                 
                                <img src="' . $BURL . 'build/images/FAQ/shm-emart-ch.png" width="100%" alt="" style="max-width: 400px; height: auto;">
                            </div>                           
                        </div>                        
                    </div>
                ',
                'locale' => 'ch',
                'weight' => 7,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Redemption: Supermarket, Hypermarket & E-commerce platform',
                'question' => '8. 为了确保参与符合资格，购买收据上必须清晰显示哪些信息？',
                'answer' => '
                购买收据必须清晰显示以下信息： 
                <ul>
                    <li>门店地址</li>
                    <li>收据号码</li>
                    <li>购买门店名称及/或其商标标识</li>
                    <li>购买日期</li>
                    <li>购买的指定参与产品</li>
                    <li>符合要求的购买金额/数量</li>
                </ul>
                <br>
                <div class="flex w-full justify-center">
                    <img src="' . $BURL . 'build/images/FAQ/shm_faq_valid_ch.png" width="100%" alt="" style="max-width: 640px; height: auto;">
                </div>
                <p>
                附上数个被拒收的购买收据示例，供您参考。
                </p>
                <BR>
                     <div class="grid" aria="flex flex-col w-full items-center">
                        <div class="faq-receipts" >
                            <div >
                                <img src="' . $BURL . 'build/images/FAQ/shm_invalid_1_ch.png" width="100%" alt="" >
                            </div>
                            <div >
                                <img src="' . $BURL . 'build/images/FAQ/shm_invalid_2_ch.png" width="100%" alt="" >
                            </div>
                            <div >                 
                                <img src="' . $BURL . 'build/images/FAQ/shm_invalid_3_ch.png" width="100%" alt="" >
                            </div>
                            <div >
                                <img src="' . $BURL . 'build/images/FAQ/shm_invalid_4_ch.png" width="100%" alt="" >
                            </div>
                            <div >
                                <img src="' . $BURL . 'build/images/FAQ/shm_invalid_5_ch.png" width="100%" alt="" >
                            </div>
                            <div >                 
                                <img src="' . $BURL . 'build/images/FAQ/shm_invalid_6_ch.png" width="100%" alt="" >
                            </div>
                             <div >
                                <img src="' . $BURL . 'build/images/FAQ/shm_invalid_7_ch.png" width="100%" alt="" >
                            </div>
                             <div >
                                <img src="' . $BURL . 'build/images/FAQ/shm_invalid_8_ch.png" width="100%" alt="" >
                            </div>
                        </div>                        
                    </div>
                 
                ',
                'locale' => 'ch',
                'weight' => 8,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Redemption: Supermarket, Hypermarket & E-commerce platform',
                'question' => '9. 请问我能合并两张购买收据以便参与本次促销活动吗?',
                'answer' => '
                很抱歉，本次促销活动并不能合并两张购买收据。本次促销活动必须以单一购买收据进行提交，方可视为有效。',
                'locale' => 'ch',
                'weight' => 9,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Redemption: Supermarket, Hypermarket & E-commerce platform',
                'question' => '10. 请问我是否需要保留原始的购买收据？',
                'answer' => '
                是的，请您务必妥善保管原始收据，以便后续验证和领取礼品。
                ',
                'locale' => 'ch',
                'weight' => 10,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Redemption: Supermarket, Hypermarket & E-commerce platform',
                'question' => '11. 我如何知道我的提交是否成功？',
                'answer' => '
                在您填写完资料、上传购买收据并提交后，系统将会弹出“感谢您的提交！”的提示讯息。与此同时，您也可以通过访问 <a href="https://bestwithcarlsberg.my/cny/en/chk_status" class="home-faq-link">https://bestwithcarlsberg.my/cny/en/chk_status</a> 并输入提交的手机号码，查询您的兑换进度。
                ',
                'locale' => 'ch',
                'weight' => 11,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Redemption: Supermarket, Hypermarket & E-commerce platform',
                'question' => '12. 如果我在成功提交 7 个工作日后，提交状态仍未更新，我该怎么办？',
                'answer' => '
                您可以通过发送邮件至我们的客户服务邮箱 <a href="mailto:carlsberg@s360plus.com" class="home-faq-link">carlsberg@s360plus.com</a>，或致电 1300-22-8899联系我们的客户服务团队。我们的客户热线服务时间为星期一至星期日，中午12时至晚上9时（公共假期除外）。',
                'locale' => 'ch',
                'weight' => 12,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Redemption: Supermarket, Hypermarket & E-commerce platform',
                'question' => '13. 一旦我的提交已通过，我该如何领取该礼品？',
                'answer' => '
                在您的提交已通过后，我们将会在90个工作日内，将该礼品寄送至您提供的收货地址。您也可以通过访问 <a href="https://bestwithcarlsberg.my/cny/en/chk_status" class="home-faq-link">https://bestwithcarlsberg.my/cny/en/chk_status</a> 并输入提交的手机号码，查询您的兑换进度。
                ',
                'locale' => 'ch',
                'weight' => 13,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Redemption: Supermarket, Hypermarket & E-commerce platform',
                'question' => '14. 每位参与者最多可以领取多少个礼品？',
                'answer' => '
                每位参与者（以手机号码和身份证号码为准）最多可领取各礼品1份。礼品数量有限，先到先得，送完即止。
                ',
                'locale' => 'ch',
                'weight' => 14,
            ],             
            [
                'campaign' => 'cny_2026',
                'category' => 'Redemption: Supermarket, Hypermarket & E-commerce platform',
                'question' => '15. 如果我收到的礼品损坏或无法使用，我该怎么办？',
                'answer' => '
                如果您收到的礼品出现损坏或无法使用的情况，请在收到礼品后的3个工作日内，通过电子邮件 <a href="mailto:carlsberg@s360plus.com" class="home-faq-link">carlsberg@s360plus.com</a>联系我们的客户服务团队。请提供损坏礼品的清晰照片或视频，以及您的姓名、手机号码和收货地址以便核实。我们将根据促销活动之条款与条件及礼品库存情况的前提下，协助处理更换事宜。
                ',
                'locale' => 'ch',
                'weight' => 15,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Redemption: Supermarket, Hypermarket & E-commerce platform',
                'question' => '16. 如果我有进一步的问题，我该联系谁？',
                'answer' => '
                您可以通过发送邮件至我们的客户服务邮箱 <a href="mailto:carlsberg@s360plus.com" class="home-faq-link">carlsberg@s360plus.com</a>，或致电 1300-22-8899联系我们的客户服务团队。我们的客户热线服务时间为星期一至星期日，中午12时至晚上9时（公共假期除外）。
                ',
                'locale' => 'ch',
                'weight' => 16,
            ], 
            //   #######################################################################
            // 2nd Category Bar, Café & Restaurant / Coffee Shop & Food Court
            //   #######################################################################
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '1. 谁具备资格参与本次促销活动?',
                'answer' => '本次促销活动公开予年满21岁或以上的非穆斯林马来西亚居民参与。主办方的员工、其关联机构、附属公司、经销商、代理商、赞助商、广告及促销代理机构的员工，以及其直系亲属，均不符合资格参与本次促销活动。',
                'locale' => 'ch',
                'weight' => 1,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '2. 我该如何参与本次促销活动并有机会赢取每周红包奖励?',
                'answer' => '
                <content>
                <table width="100%" class="home-faq-table" border="1">
                    <thead>
                        <tr>
                            <th class="home-faq-header" width="25%">渠道</th>
                            <th class="home-faq-header" width="55%">在单张收据上，凡购买​</th>
                            <th class="home-faq-header" width="20%">领取</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td rowspan="2">酒吧及餐厅</td>
                            <td>价值 RM68 的 Carlsberg Danish Pilsner 啤酒和/或 Carlsberg Smooth Draught 啤酒</td>
                            <td>1 x 参与机会</td>
                        </tr>
                        <tr>
                            <td>价值 RM68 的任意 1664 啤酒/Connor\'s 黑啤/Sapporo 啤酒/Somersby 苹果酒</td>
                            <td>2 x 参与机会</td>
                        </tr>
                        <tr>
                            <td>咖啡店 & 美食中心</td>
                            <td>
                                任意 3 大瓶的 Carlsberg 旗下产品:
                                <ul style="margin-left: 20px; list-style-type: disc;">
                                    <li>Carlsberg Danish Pilsner 啤酒 (640毫升)</li>
                                    <li>Carlsberg Smooth Draught 啤酒 (580毫升)</li>
                                    <li>Connor\'s Xtra Malt 黑啤 (640毫升)</li>
                                    <li>Sapporo 啤酒 (640毫升)</li>
                                </ul>
                            </td>
                            <td>1 x 参与机会</td>
                        </tr>
                    </tbody>
                </table>
                </content>',
                'locale' => 'ch',
                'weight' => 2,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '3. 参与本次促销活动的Carlsberg产品有哪些?',
                'answer' => '
                <p class="mb-3">酒吧及餐厅</p>
                <content>
                    <ul>
                        <li>Carlsberg Danish Pilsner 啤酒</li>
                        <li>Carlsberg Smooth Draught 啤酒</li>
                        <li>1664 啤酒</li>
                        <li>Connor\'s 黑啤</li>
                        <li>Sapporo 啤酒</li>
                        <li>Somersby 苹果酒 (任何种类)</li>
                    </ul>
                </content>
                <p class="my-3">咖啡店 & 美食中心</p>
                <content>
                    <ul>
                        <li>Carlsberg Danish Pilsner 啤酒</li>
                        <li>Carlsberg Smooth Draught 啤酒</li>
                        <li>Connor\'s 黑啤</li>
                        <li>Sapporo 啤酒</li>
                    </ul>
                </content>',
                'locale' => 'ch',
                'weight' => 3,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '4. 哪些门店参与本次促销活动?',
                'answer' => '
                <table width="100%" class="home-faq-table" border="1">
                    <tr>
                    <th class="home-faq-header" width="40%">渠道</th>
                    <th class="home-faq-header" width="60%">参与门店</th>
                    </tr>
                    <tr>
                    <td>酒吧及餐厅</td>
                    <td>指定之酒吧, 咖啡厅与餐厅, 且其店内陈列有展示 Carlsberg CNY 2026 的宣传物料。</td>
                    </tr>
                    <tr>
                    <td>咖啡店 & 美食中心</td>
                    <td>指定之咖啡店与美食中心, 且其店内陈列有展示 Carlsberg CNY 2026 的宣传物料。</td>
                    </tr>
                </table>',
                'locale' => 'ch',
                'weight' => 4,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '5. 在参与门店购买时, 我可以在哪里找到收据号码?',
                'answer' => '
                以下提供购买收据示例供您参考:<br>
                <br>
                    <div class="flex flex-col w-full items-center">
                        <div class="faq-receipts">
                            <div>
                                <img src="' . $BURL . 'build/images/FAQ/mont_brew_ch.png" width="100%" alt="" style="max-width: 400px; height: auto;">
                            </div>
                            <div>
                                <img src="' . $BURL . 'build/images/FAQ/mont_timeless_ch.png" width="100%" alt="" style="max-width: 400px; height: auto;">
                            </div>
                            <div>                 
                                <img src="' . $BURL . 'build/images/FAQ/tont_666_ch.png" width="100%" alt="" style="max-width: 400px; height: auto;">
                            </div>
                            <div>
                                <img src="' . $BURL . 'build/images/FAQ/tont_eight_ch.png" width="100%" alt="" style="max-width: 400px; height: auto;">
                            </div>                          
                        </div>                        
                    </div>',
                'locale' => 'ch',
                'weight' => 5,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '6. 为了确保参与符合资格, 购买收据上必须清晰显示哪些信息?',
                'answer' => '
                购买收据必须清晰显示以下信息:
                <content>
                <ul>
                    <li>门店地址</li>
                    <li>收据号码</li>
                    <li>购买门店名称及/或其商标标识</li>
                    <li>购买日期</li>
                    <li>购买的指定参与产品</li>
                    <li>符合要求的购买金额/数量</li>
                </ul>
                </content>
                <br>
                <div class="flex w-full justify-center">
                    <img src="' . $BURL . 'build/images/FAQ/mont_tont_faq_valid_ch.png" width="100%" alt="" style="max-width: 640px; height: auto;">
                </div>
                <br>
                <p>附上数个被拒收的购买收据示例, 供您参考。</p>
                <BR>
                <div class="grid" aria="flex flex-col w-full items-center">
                    <div class="faq-receipts" >
                        <div >
                            <img src="' . $BURL . 'build/images/FAQ/mont_tont_invalid_1_ch.png" width="100%" alt="" >
                        </div>
                        <div >
                            <img src="' . $BURL . 'build/images/FAQ/mont_tont_invalid_2_ch.png" width="100%" alt="" >
                        </div>
                        <div >                 
                            <img src="' . $BURL . 'build/images/FAQ/mont_tont_invalid_3_ch.png" width="100%" alt="" >
                        </div>
                        <div >
                            <img src="' . $BURL . 'build/images/FAQ/mont_tont_invalid_4_ch.png" width="100%" alt="" >
                        </div>
                        <div >
                            <img src="' . $BURL . 'build/images/FAQ/mont_tont_invalid_5_ch.png" width="100%" alt="" >
                        </div>
                        <div >                 
                            <img src="' . $BURL . 'build/images/FAQ/mont_tont_invalid_6_ch.png" width="100%" alt="" >
                        </div>
                        <div >
                            <img src="' . $BURL . 'build/images/FAQ/mont_tont_invalid_7_ch.png" width="100%" alt="" >
                        </div>
                        <div >
                            <img src="' . $BURL . 'build/images/FAQ/mont_tont_invalid_8_ch.png" width="100%" alt="" >
                        </div>
                    </div>                        
                </div>',
                'locale' => 'ch',
                'weight' => 6,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '7. 请问我能合并两张购买收据以便参与本次促销活动吗?',
                'answer' => '很抱歉，本次促销活动并不能合并两张购买收据。本次促销活动必须以单一购买收据进行提交，方可视为有效。',
                'locale' => 'ch',
                'weight' => 7,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '8. 请问我是否需要保留原始的购买收据?',
                'answer' => '是的，请您务必妥善保管原始收据，以便后续验证和领取红包奖励。',
                'locale' => 'ch',
                'weight' => 8,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '9. 我如何知道我的提交是否成功?',
                'answer' => '在您填写完资料、上传购买收据并提交后，系统将会弹出“感谢您的参与!”的提示讯息。',
                'locale' => 'ch',
                'weight' => 9,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '10. 每张购买收据上符合条件的参与机会是否有限制?',
                'answer' => '
                好消息，单张购买收据上符合条件的参与机会没有上限。符合条件的参与总数将根据购买标准进行计算，并按需向下取整，如下表所示。
                <br><br>
                <table width="100%" class="home-faq-table" border="1">
                    <tr>
                        <th colspan="2" class="home-faq-header">酒吧及餐厅</th>
                    </tr>
                    <tr>
                        <td colspan="2">购买收据 A</td>
                    </tr>
                    <tr>
                        <td width="60%">Carlsberg Smooth Draught 啤酒 (10 杯)</td>
                        <td width="40%">RM150</td>
                    </tr>
                    <tr>
                        <td>Carlsberg Danish Pilsner 啤酒 (1桶5瓶)</td>
                        <td>RM100</td>
                    </tr>
                    <tr>
                        <td>符合条件的总消费额</td>
                        <td>RM250</td>
                    </tr>
                    <tr>
                        <td>符合条件的参与总数</td>
                        <td>3</td>
                    </tr>
                </table>
                <br>
                <table width="100%" class="home-faq-table" border="1">
                    <tr>
                        <th colspan="2" class="home-faq-header">咖啡店 & 美食中心</th>
                    </tr>
                    <tr>
                        <td colspan="2">购买收据 B</td>
                    </tr>
                    <tr>
                        <td width="60%">Carlsberg Smooth Draught 啤酒</td>
                        <td width="40%">5 大瓶</td>
                    </tr>
                    <tr>
                        <td>Carlsberg Danish Pilsner 啤酒</td>
                        <td>12 大瓶</td>
                    </tr>
                    <tr>
                        <td>总额</td>
                        <td>17 大瓶</td>
                    </tr>
                    <tr>
                        <td>符合条件的参与总数</td>
                        <td>5</td>
                    </tr>
                </table>',
                'locale' => 'ch',
                'weight' => 10,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '11. 在整个促销期间, 提交次数是否有限制?',
                'answer' => '好消息，本次促销活动提交次数无限制。参与者可根据自身情况提交任意数量，但必须遵守条款与条件或促销资料中所述的指南和要求。',
                'locale' => 'ch',
                'weight' => 11,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '12. 每周提交周期​自何时起至何时止?',
                'answer' => '
                <table width="100%" class="home-faq-table" border="1">
                    <tr>
                    <th class="home-faq-header" width="15%" class="text-center">提交周期​</th>
                    <th class="home-faq-header" width="42%">开始日期</th>
                    <th class="home-faq-header" width="43%">截止日期</th>
                    </tr>
                    <tr>
                    <td class="text-center">1</td>
                    <td>2026年1月1日 (星期四)</td>
                    <td>2026年1月9日 (星期五)</td>
                    </tr>
                    <tr>
                    <td class="text-center">2</td>
                    <td>2026年1月10日 (星期六)</td>
                    <td>2026年1月16日 (星期五)</td>
                    </tr>
                    <tr>
                    <td class="text-center">3</td>
                    <td>2026年1月17日 (星期六)</td>
                    <td>2026年1月23日 (星期五)</td>
                    </tr>
                    <tr>
                    <td class="text-center">4</td>
                    <td>2026年1月24日 (星期六)</td>
                    <td>2026年1月30日 (星期五)</td>
                    </tr>
                    <tr>
                    <td class="text-center">5</td>
                    <td>2026年1月31日 (星期六)</td>
                    <td>2026年2月6日 (星期五)</td>
                    </tr>
                    <tr>
                    <td class="text-center">6</td>
                    <td>2026年2月7日 (星期六)</td>
                    <td>2026年2月13日 (星期五)</td>
                    </tr>
                    <tr>
                    <td class="text-center">7</td>
                    <td>2026年2月14日 (星期六)</td>
                    <td>2026年2月20日 (星期五)</td>
                    </tr>
                    <tr>
                    <td class="text-center">8</td>
                    <td>2026年2月21日 (星期六)</td>
                    <td>2026年2月28日 (星期六)</td>
                    </tr>
                </table>',
                'locale' => 'ch',
                'weight' => 12,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '13. 请问我如何知道自己是否被入围赢取每周红包奖励的获奖者之一?',
                'answer' => '
                如果您是入围的获奖者之一，您将在每周提交期结束后的 5 个工作日内，收到来自 03-7890 5046 的 WhatsApp 消息以进行获奖者验证。
                <br><br>
                验证的形式将在 48 小时内按以下方式进行:
                <br>
                <content>
                <ul class="list-disc">
                    <li>首 24 小时内: 我们将通过 WhatsApp 在不同时间段最多联系入围获奖者 3 次。</li>
                    <li>接下来的 24 小时: 若您仍未通过 WhatsApp 作出回应，我们的客户服务团队 (03-7890 5046) 将通过电话联系您 (最多 3 次)。</li>
                </ul>
                </content>
                <br>
                在验证期间，我们的团队将:
                <content>
                <ul class="list-disc">
                    <li>确认您符合参与资格 (21 岁以上，且非穆斯林)</li>
                    <li>要求您提供身份证照片 (身份证或护照) 以验证您的参与资格</li>
                    <li>我们会请您回答一项相关的问题</li>
                </ul>
                </content>
                <br>
                一旦成功验证您符合参与资格且相关的问题已回答正确，您将被正式确认为获奖者。
                <br>
                如果您认为在 48 小时内可能错过了我们的联系，请通过客户服务邮箱 <a href="mailto:carlsberg@s360plus.com" class="home-faq-link">carlsberg@s360plus.com</a> 与我们联系以获取协助。',
                'locale' => 'ch',
                'weight' => 13,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '14. 如果我没有收到入围获奖者的WhatsApp消息通知, 我该如何确认自己是否入围?',
                'answer' => '
                所有入围获奖者都会通过提交时提供的手机号码收到来自 03-7890 5046 的 WhatsApp 消息。
                <br>
                我们的获奖者验证流程将在 48 小时内完成:
                <br>
                <content>
                <ul class="list-disc">
                    <li>首 24 小时内: 我们将通过 WhatsApp 在不同时间段最多联系入围获奖者 3 次。</li>
                    <li>接下来的 24 小时: 若您仍未通过 WhatsApp 作出回应，我们的客户服务团队 (03-7890 5046) 将通过电话联系您 (最多 3 次)。</li>
                </ul>
                </content>
                <br>
                如果在 48 小时内仍未收到任何信息或电话，并且您认为自己可能已入围，请通过客户服务邮箱 <a href="mailto:carlsberg@s360plus.com" class="home-faq-link">carlsberg@s360plus.com</a> 与我们联系以获取协助。
                <br><br>
                请注意，只有在 48 小时验证期内成功接到联系的参与者，才被视为入围获奖者。',
                'locale' => 'ch',
                'weight' => 14,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '15. 如果我未能正确回答问题, 那会导致什么后果?',
                'answer' => '未能正确回答问题，主办方有权取消入围获奖者的红包奖励。',
                'locale' => 'ch',
                'weight' => 15,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '16. 如果我未能在48小时内回复、提供身份证照片或回答相关的问题, 那会导致什么后果?',
                'answer' => '未能在 48 小时内完成获奖者验证流程——包括未能 
                <br>(i) 回复 WhatsApp 消息或电话、
                <br>(ii) 提供有效身份证照片以验证年龄、及/或 
                <br>(iii) 正确回答相关的问题——将导致入围的获奖者被视为放弃获奖机会。
                <br>主办方保留全权酌情权利，根据本次促销活动的评审或入围流程选择下一位符合资格的参与者。',
                'locale' => 'ch',
                'weight' => 16,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '17. 若我是入围的获奖者之一, 基于安全因素考量而拒绝提供身份证照片进行验证, 那会导致什么后果?',
                'answer' => '如未能提供身份证照片以进行验证，主办方有权取消获入围获奖者的红包奖励。请放心，所有个人信息将严格保密，仅用于核实身份之目的。',
                'locale' => 'ch',
                'weight' => 17,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '18. 我什么时候可以收到赢得的红包奖励?',
                'answer' => '获奖者确认后，我们将在公布获奖名单之日起 10 个工作日内与您联系，以便获取银行信息进行转账，或收集您的地址以安排支票亲自寄送。',
                'locale' => 'ch',
                'weight' => 18,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '19. 在整个促销期间, 总共有多少名竞赛获奖者?',
                'answer' => '
                <table width="100%" class="home-faq-table" border="1">
                    <tr>
                    <th class="home-faq-header" width="33%">奖励等级</th>
                    <th class="home-faq-header" width="33%">奖励金额</th>
                    <th class="home-faq-header" width="17%">每周获奖者</th>
                    <th class="home-faq-header" width="17%">总获奖者</th>
                    </tr>
                    <tr>
                    <td>大奖</td>
                    <td>RM3,888 红包</td>
                    <td>8</td>
                    <td>64</td>
                    </tr>
                    <tr>
                    <td>一等奖</td>
                    <td>RM178 红包</td>
                    <td>88</td>
                    <td>704</td>
                    </tr>
                </table>',
                'locale' => 'ch',
                'weight' => 19,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Bar, Café & Restaurant / Coffee Shop & Food Court',
                'question' => '20. 如果我有进一步的问题, 我该联系谁?',
                'answer' => '您可以通过发送邮件至我们的客户服务邮箱 <a href="mailto:carlsberg@s360plus.com" class="home-faq-link">carlsberg@s360plus.com</a>，或致电 1300-22-8899 联系我们的客户服务团队。我们的客户热线服务时间为星期一至星期日，中午 12 时至晚上 9 时 (公共假期除外)。',
                'locale' => 'ch',
                'weight' => 20,
            ],
            //   #######################################################################
            // 3rd Category Contest: 99 Speedmart
            //   #######################################################################
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '1. 谁具备资格参与本次促销活动?',
                'answer' => '本次促销活动公开予年满21岁或以上的非穆斯林马来西亚居民参与。主办方的员工、其关联机构、附属公司、经销商、代理商、赞助商、广告及促销代理机构的员工, 以及其直系亲属, 均不符合资格参与本次促销活动。',
                'locale' => 'ch',
                'weight' => 1,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '2. 我该如何参与本次促销活动并有机会赢取每周红包奖励?',
                'answer' => '
                <table width="100%" class="home-faq-table" border="1">
                <tbody>
                    <tr>
                    <th class="home-faq-header" width="30%">渠道</th>
                    <th class="home-faq-header" width="50%">单张收据凡购买</th>
                    <th class="home-faq-header" width="20%">领取</th>
                    </tr>
                    <tr>
                    <td>99 Speedmart</td>
                    <td>
                        <p class="my-2">单张收据购买以下任意参与品牌的 1 罐 (320毫升/500毫升) 或 1 瓶 (325毫升/330毫升/580毫升/640毫升):</p>
                        <content>
                        <ul class="list-disc">
                            <li>Carlsberg Danish Pilsner 啤酒</li>
                            <li>Carlsberg Smooth Draught 啤酒</li>
                            <li>Carlsberg Special Brew 啤酒</li>
                            <li>任意 1664 啤酒</li>
                            <li>任意 Somersby 苹果酒</li>
                            <li>Connor\'s 黑啤</li>
                            <li>Sapporo 啤酒</li>
                            <li>SKOL 啤酒</li>
                            <li>Royal Stout 黑啤</li>
                        </ul>
                        </content>
                    </td>
                    <td>1 次参与机会</td>
                    </tr>
                </tbody>
                </table>',
                'locale' => 'ch',
                'weight' => 2,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '3. 参与本次促销活动的Carlsberg产品有哪些?',
                'answer' => '
                <content>
                    <ul>
                        <li>Carlsberg Danish Pilsner 啤酒</li>
                        <li>Carlsberg Smooth Draught 啤酒</li>
                        <li>Carlsberg Special Brew 啤酒</li>
                        <li>任意 1664 啤酒</li>
                        <li>任意 Somersby 苹果酒</li>
                        <li>Connor\'s 黑啤</li>
                        <li>Sapporo 啤酒</li>
                        <li>SKOL 啤酒</li>
                        <li>Royal Stout 黑啤</li>
                    </ul>
                </content>',
                'locale' => 'ch',
                'weight' => 3,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '4. 99 Speedmart 的参与门店有哪些?',
                'answer' => '
                <table width="100%" class="home-faq-table" border="1">
                    <tr>
                    <th class="home-faq-header" width="40%">渠道</th>
                    <th class="home-faq-header" width="60%">参与门店</th>
                    </tr>
                    <tr>
                    <td>99 Speedmart</td>
                    <td>全马各门店</td>
                    </tr>
                </table>',
                'locale' => 'ch',
                'weight' => 4,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '5. 在99 Speedmart 的参与门店购买时, 我可以在哪里找到收据号码?',
                'answer' => '
                以下提供购买收据示例供您参考:<br>
                <br>
                <div class="flex flex-col w-full items-center">
                    <div class="faq-receipts">
                        <div>
                            <img src="' . $BURL . 'build/images/FAQ/S99_receipt_ch.png" width="100%" alt="" style="max-width: 400px; height: auto;">
                        </div>                       
                    </div>                        
                </div>',
                'locale' => 'ch',
                'weight' => 5,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '6. 为了确保参与符合资格, 购买收据上必须清晰显示哪些信息?',
                'answer' => '
                购买收据必须清晰显示以下信息:
                <content>
                <ul>
                    <li>门店地址</li>
                    <li>收据号码</li>
                    <li>购买门店名称及/或其商标标识</li>
                    <li>购买日期</li>
                    <li>购买的指定参与产品</li>
                    <li>符合要求的购买金额/数量</li>
                </ul>
                </content>
                <br>
                <div class="flex w-full justify-center">
                    <img src="' . $BURL . 'build/images/FAQ/S99_valid_ch.png" width="100%" alt="" style="max-width: 640px; height: auto;">
                </div>
                <br>
                <p>附上数个被拒收的购买收据示例, 供您参考。</p>
                <BR>
                <div class="grid" aria="flex flex-col w-full items-center">
                    <div class="faq-receipts" >
                        <div >
                            <img src="' . $BURL . 'build/images/FAQ/S99_invalid_non_participating_product_ch.png" width="100%" alt="" >
                        </div>
                        <div >
                            <img src="' . $BURL . 'build/images/FAQ/S99_invalid_missing_outlet_ch.png" width="100%" alt="" >
                        </div>
                        <div >                 
                            <img src="' . $BURL . 'build/images/FAQ/S99_invalid_receipt_notclear_ch.png" width="100%" alt="" >
                        </div>
                        <div >
                            <img src="' . $BURL . 'build/images/FAQ/S99_invalid_non_participating_product_ch.png" width="100%" alt="" >
                        </div>
                        <div >
                            <img src="' . $BURL . 'build/images/FAQ/S99_invalid_missing_receipt_number_ch.png" width="100%" alt="" >
                        </div>
                        <div >                 
                            <img src="' . $BURL . 'build/images/FAQ/S99_invalid_outside_promo_period_ch.png" width="100%" alt="" >
                        </div>
                        <div >
                            <img src="' . $BURL . 'build/images/FAQ/S99_invalid_duplicate_receipt_ch.png" width="100%" alt="" >
                        </div>
                        <div >
                            <img src="' . $BURL . 'build/images/FAQ/S99_invalid_missing_outletaddress_ch.png" width="100%" alt="" >
                        </div>
                    </div>                        
                </div>',
                'locale' => 'ch',
                'weight' => 6,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '7. 请问我能合并两张购买收据以便参与本次促销活动吗?',
                'answer' => '很抱歉, 本次促销活动并不能合并两张购买收据。本次促销活动必须以单一购买收据进行提交, 方可视为有效。',
                'locale' => 'ch',
                'weight' => 7,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '8. 请问我是否需要保留原始的购买收据?',
                'answer' => '是的, 请您务必妥善保管原始收据, 以便后续验证和领取红包奖励。',
                'locale' => 'ch',
                'weight' => 8,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '9. 我如何知道我的提交是否成功?',
                'answer' => '在您填写完资料、上传购买收据并提交后, 系统将会弹出“感谢您的参与!”的提示讯息。',
                'locale' => 'ch',
                'weight' => 9,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '10. 每张购买收据上符合条件的参与机会是否有限制?',
                'answer' => '每张符合条件的收据仅可获得一次参与机会。无论购买了多少参与品牌的罐装产品, 只要该收据在促销期间显示单笔交易购买至少一样参与品牌。
                <br><br>
                在整个促销期间, 提交的不同收据数量没有上限。每张符合条件的有效收据可获得一次参与机会。',
                'locale' => 'ch',
                'weight' => 10,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '11. 在整个促销期间, 提交次数是否有限制?',
                'answer' => '好消息, 本次促销活动提交次数无限制。参与者可根据自身情况提交任意数量, 但必须遵守条款与条件或促销资料中所述的指南和要求。',
                'locale' => 'ch',
                'weight' => 11,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '12. 每周促销期自何时起至何时止?',
                'answer' => '
                <table width="100%" class="home-faq-table" border="1">
                    <tr>
                    <th class="home-faq-header" width="15%" class="text-center">促销活动周</th>
                    <th class="home-faq-header" width="42%">开始日期</th>
                    <th class="home-faq-header" width="43%">截止日期</th>
                    </tr>
                    <tr>
                    <td class="text-center">1</td>
                    <td>2026年1月1日 (星期四)</td>
                    <td>2026年1月9日 (星期五)</td>
                    </tr>
                    <tr>
                    <td class="text-center">2</td>
                    <td>2026年1月10日 (星期六)</td>
                    <td>2026年1月16日 (星期五)</td>
                    </tr>
                    <tr>
                    <td class="text-center">3</td>
                    <td>2026年1月17日 (星期六)</td>
                    <td>2026年1月23日 (星期五)</td>
                    </tr>
                    <tr>
                    <td class="text-center">4</td>
                    <td>2026年1月24日 (星期六)</td>
                    <td>2026年1月30日 (星期五)</td>
                    </tr>
                    <tr>
                    <td class="text-center">5</td>
                    <td>2026年1月31日 (星期六)</td>
                    <td>2026年2月6日 (星期五)</td>
                    </tr>
                    <tr>
                    <td class="text-center">6</td>
                    <td>2026年2月7日 (星期六)</td>
                    <td>2026年2月13日 (星期五)</td>
                    </tr>
                    <tr>
                    <td class="text-center">7</td>
                    <td>2026年2月14日 (星期六)</td>
                    <td>2026年2月20日 (星期五)</td>
                    </tr>
                    <tr>
                    <td class="text-center">8</td>
                    <td>2026年2月21日 (星期六)</td>
                    <td>2026年2月28日 (星期六)</td>
                    </tr>
                </table>',
                'locale' => 'ch',
                'weight' => 12,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '13. 请问我如何知道自己是否被入围赢取每周红包奖励的获奖者之一?',
                'answer' => '
                如果您是入围的获奖者之一, 您将在每周指定促销期结束后的 5 个工作日内, 收到来自 03-7890 5046 的 WhatsApp 消息以进行获奖者验证。
                <br><br>
                验证的形式将在 48 小时内按以下方式进行:
                <br>
                <content>
                <ul class="list-disc">
                    <li>首 24 小时内: 我们将通过 WhatsApp 在不同时间段最多联系入围获奖者 3 次。</li>
                    <li>接下来的 24 小时: 若您仍未通过 WhatsApp 作出回应, 我们的客户服务团队 (03-7890 5046) 将通过电话联系您 (最多 3 次)。</li>
                </ul>
                </content>
                <br>
                在验证期间, 我们的团队将:
                <content>
                <ul class="list-disc">
                    <li>确认您符合参与资格 (21 岁以上, 且非穆斯林)</li>
                    <li>要求您提供身份证照片 (身份证或护照) 以验证您的参与资格</li>
                    <li>我们会请您回答一项相关的问题</li>
                </ul>
                </content>
                <br>
                一旦成功验证您符合参与资格且相关的问题已回答正确, 您将被正式确认为获奖者。
                <br>
                如果您认为在 48 小时内可能错过了我们的联系, 请通过客户服务邮箱 <a href="mailto:carlsberg@s360plus.com" class="home-faq-link">carlsberg@s360plus.com</a> 与我们联系以获取协助。',
                'locale' => 'ch',
                'weight' => 13,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '14. 如果我没有收到入围获奖者的WhatsApp 消息通知, 我该如何确认自己是否入围?',
                'answer' => '
                所有入围获奖者都会通过提交时提供的手机号码收到来自 03-7890 5046 的 WhatsApp 消息。
                <br>
                我们的获奖者验证流程将在 48 小时内完成:
                <br>
                <content>
                <ul class="list-disc">
                    <li>首 24 小时内: 我们将通过 WhatsApp 在不同时间段最多联系入围获奖者 3 次。</li>
                    <li>接下来的 24 小时: 若您仍未通过 WhatsApp 作出回应, 我们的客户服务团队 (03-7890 5046) 将通过电话联系您 (最多 3 次)。</li>
                </ul>
                </content>
                <br>
                如果在 48 小时内仍未收到任何信息或电话, 并且您认为自己可能已入围, 请通过客户服务邮箱 <a href="mailto:carlsberg@s360plus.com" class="home-faq-link">carlsberg@s360plus.com</a> 与我们联系以获取协助。
                <br><br>
                请注意, 只有在 48 小时验证期内成功接到联系的参与者, 才被视为入围获奖者。',
                'locale' => 'ch',
                'weight' => 14,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '15. 如果我未能正确回答问题, 那会导致什么后果?',
                'answer' => '未能正确回答问题, 主办方有权取消入围获奖者的红包奖励。',
                'locale' => 'ch',
                'weight' => 15,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '16. 如果我未能在48小时内回复、提供身份证照片或回答相关的问题, 那会导致什么后果?',
                'answer' => '未能在 48 小时内完成获奖者验证流程 包括未能 
                <br>(i) 回复 WhatsApp 消息或电话、
                <br>(ii) 提供有效身份证照片以验证年龄、及/或 
                <br>(iii) 正确回答相关的问题——将导致入围的获奖者被视为放弃获奖机会。
                <br>主办方保留全权酌情权利, 根据本次促销活动的评审或入围流程选择下一位符合资格的参与者。',
                'locale' => 'ch',
                'weight' => 16,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '17. 若我是入围的获奖者之一, 基于安全因素考量而拒绝提供身份证照片进行验证, 那会导致什么后果?',
                'answer' => '如未能提供身份证照片以进行验证, 主办方有权取消获入围获奖者的红包奖励。请放心, 所有个人信息将严格保密, 仅用于核实身份之目的。',
                'locale' => 'ch',
                'weight' => 17,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '18. 我什么时候可以收到赢得的红包奖励?',
                'answer' => '获奖者确认后, 我们将在公布获奖名单之日起 30 个工作日内与您联系, 以便获取银行信息进行转账, 或收集您的地址以安排支票亲自寄送。',
                'locale' => 'ch',
                'weight' => 18,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '19. 我什么时候可以收到赢得的电子钱包充值奖励?',
                'answer' => '获奖者确认后, 我们将在公布获奖名单之日起 30 个工作日内通过 WhatsApp 通知您, 并协助安排将奖励金额汇入您的电子钱包。',
                'locale' => 'ch',
                'weight' => 19,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '20. 在整个促销期间, 总共有多少名竞赛获奖者?',
                'answer' => '
                <table width="100%" class="home-faq-table" border="1">
                    <tr>
                    <th class="home-faq-header" width="30%">奖励等级</th>
                    <th class="home-faq-header" width="30%">奖励金额</th>
                    <th class="home-faq-header" width="20%">每周获奖者</th>
                    <th class="home-faq-header" width="20%">总获奖者</th>
                    </tr>
                    <tr>
                    <td>大奖</td>
                    <td>RM1,788 红包</td>
                    <td>11</td>
                    <td>88</td>
                    </tr>
                    <tr>
                    <td>一等奖</td>
                    <td>RM99 电子钱包充值</td>
                    <td>168</td>
                    <td>1,344</td>
                    </tr>
                </table>',
                'locale' => 'ch',
                'weight' => 20,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '21. 每位参与者在竞赛中最多可以赢取多少份每周红包奖励?',
                'answer' => '每位参与者 (以手机号码和身份证号码为准) 在整个促销期间仅可赢取一份大奖或一份一等奖。',
                'locale' => 'ch',
                'weight' => 21,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: 99 Speedmart',
                'question' => '22. 如果我有进一步的问题, 我该联系谁?',
                'answer' => '您可以通过发送邮件至我们的客户服务邮箱 <a href="mailto:carlsberg@s360plus.com" class="home-faq-link">carlsberg@s360plus.com</a>, 或致电 1300-22-8899 联系我们的客户服务团队。我们的客户热线服务时间为星期一至星期日, 中午 12 时至晚上 9 时 (公共假期除外)。',
                'locale' => 'ch',
                'weight' => 22,
            ],
            // 
            // 4th Category Contest: Convenience Store & Mini Market
            // 
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '1. 谁具备资格参与本次促销活动?',
                'answer' => '本次促销活动公开予年满21岁或以上的非穆斯林马来西亚居民参与。主办方的员工、其关联机构、附属公司、经销商、代理商、赞助商、广告及促销代理机构的员工, 以及其直系亲属, 均不符合资格参与本次促销活动。',
                'locale' => 'ch',
                'weight' => 1,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '2. 我该如何参与本次促销活动, 以领取电子钱包充值奖励, 并有机会赢取一部 iPhone 17 Pro?',
                'answer' => '
                <table width="100%" class="home-faq-table" border="1">
                <tbody>
                    <tr>
                    <th class="home-faq-header" width="30%">渠道</th>
                    <th class="home-faq-header" width="50%">在单张收据上, 凡购买</th>
                    <th class="home-faq-header" width="20%">领取</th>
                    </tr>
                    <tr>
                    <td>便利商店 & 迷你市场</td>
                    <td>
                        <p class="my-2">Carlsberg 旗下产品满 RM20:</p>
                        <content>
                        <ul class="list-disc">
                            <li>Carlsberg Danish Pilsner 啤酒</li>
                            <li>Carlsberg Smooth Draught 啤酒</li>
                            <li>Carlsberg Special Brew 啤酒</li>
                            <li>任意 1664 啤酒</li>
                            <li>任意 Somersby 苹果酒</li>
                            <li>Connor\'s 黑啤</li>
                            <li>Sapporo 啤酒</li>
                            <li>SKOL 啤酒</li>
                            <li>Royal Stout 黑啤</li>
                        </ul>
                        </content>
                    </td>
                    <td>1 次参与机会 & RM5 电子钱包充值</td>
                    </tr>
                </tbody>
                </table>',
                'locale' => 'ch',
                'weight' => 2,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '3. 参与本次促销活动的Carlsberg 产品有哪些?',
                'answer' => '
                <content>
                    <ul>
                        <li>Carlsberg Danish Pilsner 啤酒</li>
                        <li>Carlsberg Smooth Draught 啤酒</li>
                        <li>Carlsberg Special Brew 啤酒</li>
                        <li>任意 1664 啤酒</li>
                        <li>任意 Somersby 苹果酒</li>
                        <li>Connor\'s 黑啤</li>
                        <li>Sapporo 啤酒</li>
                        <li>SKOL 啤酒</li>
                        <li>Royal Stout 黑啤</li>
                    </ul>
                </content>',
                'locale' => 'ch',
                'weight' => 3,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '4. 便利商店和迷你市场的参与门店有哪些?',
                'answer' => '
                <table width="100%" class="home-faq-table" border="1">
                    <tr>
                    <th class="home-faq-header" width="40%">渠道</th>
                    <th class="home-faq-header" width="60%">参与门店</th>
                    </tr>
                    <tr>
                    <td>便利商店 & 迷你市场</td>
                    <td>指定之便利商店与迷你市场, 且其店内陈列有展示 Carlsberg CNY 2026 的宣传物料。</td>
                    </tr>
                </table>',
                'locale' => 'ch',
                'weight' => 4,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '5. 在便利商店和迷你市场的参与门店购买时, 我可以在哪里找到收据号码?',
                'answer' => '
                以下提供一些购买收据示例供您参考:<br>
                <div class="flex flex-col w-full items-center">
                    <div class="faq-receipts">
                        <div>
                            <img src="' . $BURL . 'build/images/FAQ/CVS_7e_ch.png" width="100%" alt="" style="max-width: 400px; height: auto;">
                        </div>                       
                        <div>
                            <img src="' . $BURL . 'build/images/FAQ/CVS_mix_store_ch.png" width="100%" alt="" style="max-width: 400px; height: auto;">
                        </div>                       
                        <div>
                            <img src="' . $BURL . 'build/images/FAQ/CVS_orange_cvs_ch.png" width="100%" alt="" style="max-width: 400px; height: auto;">
                        </div>                       
                    </div>                        
                </div>',
                'locale' => 'ch',
                'weight' => 5,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '6. 为了确保参与符合资格, 购买收据上必须清晰显示哪些信息?',
                'answer' => '
                购买收据必须清晰显示以下信息:
                <content>
                <ul>
                    <li>门店地址</li>
                    <li>收据号码</li>
                    <li>购买门店名称及/或其商标标识</li>
                    <li>购买日期</li>
                    <li>购买的指定参与产品</li>
                    <li>符合要求的购买金额/数量</li>
                </ul>
                </content>
                <br>
                <div class="flex w-full justify-center">
                    <img src="' . $BURL . 'build/images/FAQ/CVS_valid_ch.png" width="100%" alt="" style="max-width: 640px; height: auto;">
                </div>
                <br>
                <p>附上数个被拒收的购买收据示例, 供您参考。</p>
                <BR>
                <div class="grid" aria="flex flex-col w-full items-center">
                    <div class="faq-receipts" >
                        <div >
                            <img src="' . $BURL . 'build/images/FAQ/CVS_invalid_non_participating_product_ch.png" width="100%" alt="" >
                        </div>
                        <div >
                            <img src="' . $BURL . 'build/images/FAQ/CVS_invalid_missing_outlet_ch.png" width="100%" alt="" >
                        </div>
                        <div >                 
                            <img src="' . $BURL . 'build/images/FAQ/CVS_invalid_receipt_notclear_ch.png" width="100%" alt="" >
                        </div>
                        <div >
                            <img src="' . $BURL . 'build/images/FAQ/CVS_invalid_non_participating_product_ch.png" width="100%" alt="" >
                        </div>
                        <div >
                            <img src="' . $BURL . 'build/images/FAQ/CVS_invalid_missing_receipt_number_ch.png" width="100%" alt="" >
                        </div>
                        <div >                 
                            <img src="' . $BURL . 'build/images/FAQ/CVS_invalid_outside_promo_period_ch.png" width="100%" alt="" >
                        </div>
                        <div >
                            <img src="' . $BURL . 'build/images/FAQ/CVS_invalid_duplicate_receipt_ch.png" width="100%" alt="" >
                        </div>
                        <div >
                            <img src="' . $BURL . 'build/images/FAQ/CVS_invalid_missing_outletaddress_ch.png" width="100%" alt="" >
                        </div>
                    </div>                        
                </div>',
                'locale' => 'ch',
                'weight' => 6,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '7. 请问我能合并两张购买收据以便参与本次促销活动吗?',
                'answer' => '很抱歉, 本次促销活动并不能合并两张购买收据。本次促销活动必须以单一购买收据进行提交, 方可视为有效。',
                'locale' => 'ch',
                'weight' => 7,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '8. 请问我是否需要保留原始的购买收据?',
                'answer' => '是的, 请您务必妥善保管原始收据, 以便后续验证和领取礼品。',
                'locale' => 'ch',
                'weight' => 8,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '9. 我如何知道我的提交是否成功?',
                'answer' => '在您填写完资料、上传购买收据并提交后, 系统将会弹出“感谢您的参与!”的提示讯息。',
                'locale' => 'ch',
                'weight' => 9,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '10. 每张购买收据上符合条件的参与机会是否有限制?',
                'answer' => '
                好消息, 单张购买收据上符合条件的参与机会没有上限。符合条件的参与总数将根据购买标准进行计算, 并按需向下取整, 如下表所示。
                <br><br>
                <table width="100%" class="home-faq-table" border="1">
                    <tr>
                        <th colspan="2" class="home-faq-header">购买收据 A</th>
                    </tr>
                    <tr>
                        <td width="60%">Carlsberg Smooth Draught 啤酒 (500毫升)</td>
                        <td width="40%">RM15</td>
                    </tr>
                    <tr>
                        <td>Sapporo 啤酒</td>
                        <td>RM20</td>
                    </tr>
                    <tr>
                        <td>符合条件的总消费额</td>
                        <td>RM35</td>
                    </tr>
                    <tr>
                        <td>符合条件的参与总数</td>
                        <td>1</td>
                    </tr>
                </table>
                <br>
                <table width="100%" class="home-faq-table" border="1">
                    <tr>
                        <th colspan="2" class="home-faq-header">购买收据 B</th>
                    </tr>
                    <tr>
                        <td width="60%">Carlsberg Danish Pilsner 啤酒 (320毫升)</td>
                        <td width="40%">RM100</td>
                    </tr>
                    <tr>
                        <td>Somersby 苹果酒</td>
                        <td>RM20</td>
                    </tr>
                    <tr>
                        <td>符合条件的总消费额</td>
                        <td>RM120</td>
                    </tr>
                    <tr>
                        <td>符合条件的参与总数</td>
                        <td>6</td>
                    </tr>
                </table>
                <br>
                <table width="100%" class="home-faq-table" border="1">
                    <tr>
                        <th colspan="2" class="home-faq-header">购买收据 C</th>
                    </tr>
                    <tr>
                        <td width="60%">1664 Rose 啤酒</td>
                        <td width="40%">RM100</td>
                    </tr>
                    <tr>
                        <td>Connor\'s 黑啤</td>
                        <td>RM100</td>
                    </tr>
                    <tr>
                        <td>符合条件的总消费额</td>
                        <td>RM200</td>
                    </tr>
                    <tr>
                        <td>符合条件的参与总数</td>
                        <td>10</td>
                    </tr>
                </table>
                <br>
                <table width="100%" class="home-faq-table" border="1">
                    <tr>
                        <th colspan="2" class="home-faq-header">购买收据 D</th>
                    </tr>
                    <tr>
                        <td width="60%">Carlsberg Smooth Draught 啤酒 (320毫升)</td>
                        <td width="40%">RM10</td>
                    </tr>
                    <tr>
                        <td>符合条件的总消费额</td>
                        <td>RM10</td>
                    </tr>
                    <tr>
                        <td>符合条件的参与总数</td>
                        <td>0</td>
                    </tr>
                </table>',
                'locale' => 'ch',
                'weight' => 10,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '11. 在整个促销期间, 提交次数是否有限制?',
                'answer' => '好消息, 本次促销活动提交次数无限制。参与者可根据自身情况提交任意数量, 但必须遵守条款与条件或促销资料中所述的指南和要求。',
                'locale' => 'ch',
                'weight' => 11,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '12. 每位参与者最多可以领取多少份电子钱包充值奖励?',
                'answer' => '每位参与者 (以手机号码和身份证号码为准) 最多可领取 5 份电子钱包充值奖励。电子钱包充值奖励数量有限, 先到先得, 送完即止。',
                'locale' => 'ch',
                'weight' => 12,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '13. 请问我如何知道自己是否被入围赢取 iPhone 17 Pro 的获奖者之一?',
                'answer' => '
                如果您是入围的获奖者之一, 您将在促销活动结束后的 30 个工作日内, 收到来自 03-7890 5046 的 WhatsApp 消息以进行获奖者验证。
                <br><br>
                验证的形式将在 48 小时内按以下方式进行:
                <br>
                <content>
                <ul class="list-disc">
                    <li>首 24 小时内: 我们将通过 WhatsApp 在不同时间段最多联系入围获奖者 3 次。</li>
                    <li>接下来的 24 小时: 若您仍未通过 WhatsApp 作出回应, 我们的客户服务团队 (03-7890 5046) 将通过电话联系您 (最多 3 次)。</li>
                </ul>
                </content>
                <br>
                在验证期间, 我们的团队将:
                <content>
                <ul class="list-disc">
                    <li>确认您符合参与资格 (21 岁以上, 且非穆斯林)</li>
                    <li>要求您提供身份证照片 (身份证或护照) 以验证您的参与资格</li>
                    <li>我们会请您回答一项相关的问题</li>
                </ul>
                </content>
                <br>
                一旦成功验证您符合参与资格且相关的问题已回答正确, 您将被正式确认为获奖者。
                <br>
                如果您认为在 48 小时内可能错过了我们的联系, 请通过客户服务邮箱 <a href="mailto:carlsberg@s360plus.com" class="home-faq-link">carlsberg@s360plus.com</a> 与我们联系以获取协助。',
                'locale' => 'ch',
                'weight' => 13,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '14. 如果我没有收到入围获奖者的WhatsApp 消息通知, 我该如何确认自己是否入围?',
                'answer' => '
                所有入围获奖者都会通过提交时提供的手机号码收到来自 03-7890 5046 的 WhatsApp 消息。
                <br>
                我们的获奖者验证流程将在 48 小时内完成:
                <br>
                <content>
                <ul class="list-disc">
                    <li>首 24 小时内: 我们将通过 WhatsApp 在不同时间段最多联系入围获奖者 3 次。</li>
                    <li>接下来的 24 小时: 若您仍未通过 WhatsApp 作出回应, 我们的客户服务团队 (03-7890 5046) 将通过电话联系您 (最多 3 次)。</li>
                </ul>
                </content>
                <br>
                如果在 48 小时内仍未收到任何信息或电话, 并且您认为自己可能已入围, 请通过客户服务邮箱 <a href="mailto:carlsberg@s360plus.com" class="home-faq-link">carlsberg@s360plus.com</a> 与我们联系以获取协助。
                <br><br>
                请注意, 只有在 48 小时验证期内成功接到联系的参与者, 才被视为入围获奖者。',
                'locale' => 'ch',
                'weight' => 14,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '15. 如果我未能正确回答问题, 那会导致什么后果?',
                'answer' => '未能正确回答问题, 主办方有权取消入围获奖者的礼品。',
                'locale' => 'ch',
                'weight' => 15,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '16. 如果我未能在48小时内回复、提供身份证照片或回答相关的问题, 那会导致什么后果?',
                'answer' => '未能在 48 小时内完成获奖者验证流程 包括未能 
                <br>(i) 回复 WhatsApp 消息或电话、
                <br>(ii) 提供有效身份证照片以验证年龄、及/或 
                <br>(iii) 正确回答相关的问题——将导致入围的获奖者被视为放弃获奖机会。
                <br>主办方保留全权酌情权利, 根据本次促销活动的评审或入围流程选择下一位符合资格的参与者。',
                'locale' => 'ch',
                'weight' => 16,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '17. 若我是入围的获奖者之一, 基于安全因素考量而拒绝提供身份证照片进行验证, 那会导致什么后果?',
                'answer' => '如未能提供身份证照片以进行验证, 主办方有权取消获入围获奖者的礼品。请放心, 所有个人信息将严格保密, 仅用于核实身份之目的。',
                'locale' => 'ch',
                'weight' => 17,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '18. 我什么时候可以收到赢得的iPhone 17 Pro?',
                'answer' => '获奖者确认后, 礼品将在官方网站公布获奖名单之日起 60 个工作日内派送。',
                'locale' => 'ch',
                'weight' => 18,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '19. 在整个促销期间, 总共有多少名 iPhone 17 Pro 的竞赛获奖者?',
                'answer' => '在整个促销期间, 将选出 8 名 iPhone 17 Pro 获奖者。',
                'locale' => 'ch',
                'weight' => 19,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '20. 每位参与者在竞赛中最多可以赢取多少部 iPhone 17 Pro?',
                'answer' => '每位参与者 (以手机号码和身份证号码为准) 最多可赢取一部 iPhone 17 Pro。',
                'locale' => 'ch',
                'weight' => 20,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '21. 如果我已兑换RM5电子钱包充值奖励, 是否仍可参与竞赛并有机会赢取 iPhone 17 Pro?',
                'answer' => '是的。每位参与者 (以手机号码和身份证号码为准) 最多可赢取一部 iPhone 17 Pro, 并可兑换最多 5 份电子钱包充值奖励。',
                'locale' => 'ch',
                'weight' => 21,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '22. 我可以要求更换iPhone 17 Pro 的型号或颜色吗?',
                'answer' => '很抱歉, iPhone 17 Pro 的型号及颜色均为指定款式, 恕不接受更换。',
                'locale' => 'ch',
                'weight' => 22,
            ],
            [
                'campaign' => 'cny_2026',
                'category' => 'Contest: Convenience Store & Mini Market',
                'question' => '23. 如果我有进一步的问题, 我该联系谁?',
                'answer' => '您可以通过发送邮件至我们的客户服务邮箱 <a href="mailto:carlsberg@s360plus.com" class="home-faq-link">carlsberg@s360plus.com</a>, 或致电 1300-22-8899 联系我们的客户服务团队。我们的客户热线服务时间为星期一至星期日, 中午 12 时至晚上 9 时 (公共假期除外)。',
                'locale' => 'ch',
                'weight' => 23,
            ],
        ];
        
        $this->truncateTable("faq");

        for ($i=0; $i < count($_FAQDATA_EN); $i++) {
            $feFAQ = new Faq();
            $feFAQ->setCampaign($_FAQDATA_EN[$i]['campaign']);
            $feFAQ->setCategory($_FAQDATA_EN[$i]['category']);
            $feFAQ->setQuestion($_FAQDATA_EN[$i]['question']);
            $feFAQ->setAnswer($_FAQDATA_EN[$i]['answer']);
            $feFAQ->setLocale($_FAQDATA_EN[$i]['locale']);
            $feFAQ->setWeight($_FAQDATA_EN[$i]['weight']);
            $this->manager->persist($feFAQ);     
            $this->manager->flush();
        }

        for ($i=0; $i < count($_FAQDATA_CH); $i++) {
            $feFAQ = new Faq();
            $feFAQ->setCampaign($_FAQDATA_CH[$i]['campaign']);
            $feFAQ->setCategory($_FAQDATA_CH[$i]['category']);
            $feFAQ->setQuestion($_FAQDATA_CH[$i]['question']);
            $feFAQ->setAnswer($_FAQDATA_CH[$i]['answer']);
            $feFAQ->setLocale($_FAQDATA_CH[$i]['locale']);
            $feFAQ->setWeight($_FAQDATA_CH[$i]['weight']);
            $this->manager->persist($feFAQ);     
            $this->manager->flush();
        }

        $output->writeln([
            '',
            'FAQ generated'
        ]
        );
        return 0;
    }

    private function truncateTable (string $tableName): bool {
        $connection = $this->manager->getConnection();
        try {
            $sql = "TRUNCATE TABLE ".$tableName;       
            $stmt = $connection->prepare($sql);
            $result = $stmt->executeQuery();
        } catch (\Exception $e) {
            try {
                fwrite(STDERR, print_r('Can\'t truncate faq table ' . $tableName . '. Reason: ' . $e->getMessage(), TRUE));
                $connection->rollback();
                return false;
            } catch (ConnectionException $connectionException) {
                fwrite(STDERR, print_r('Can\'t rollback truncating faq table ' . $tableName . '. Reason: ' . $connectionException->getMessage(), TRUE));
                return false;
            }
        }
        return true;
    }
}
