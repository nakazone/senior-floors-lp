<?php
/**
 * Email Confirmation Template
 * FASE 1 - MÓDULO 03: Confirmação Automática ao Cliente
 * 
 * Template HTML para email de confirmação enviado ao lead após cadastro
 */

function getEmailConfirmationTemplate($lead_name, $lead_email, $lead_phone, $lead_zipcode, $lead_message = '') {
    $name = htmlspecialchars($lead_name, ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars($lead_email, ENT_QUOTES, 'UTF-8');
    $phone = htmlspecialchars($lead_phone, ENT_QUOTES, 'UTF-8');
    $zipcode = htmlspecialchars($lead_zipcode, ENT_QUOTES, 'UTF-8');
    $message = !empty($lead_message) ? nl2br(htmlspecialchars($lead_message, ENT_QUOTES, 'UTF-8')) : '';
    
    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You - Senior Floors</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1a2036 0%, #252b47 100%); padding: 30px; text-align: center; border-radius: 8px 8px 0 0;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px;">Senior Floors</h1>
                            <p style="color: #d4af37; margin: 10px 0 0 0; font-size: 16px;">Quality Flooring Solutions</p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #1a2036; margin-top: 0; font-size: 24px;">Thank You, {$name}!</h2>
                            
                            <p style="color: #333; font-size: 16px; line-height: 1.6; margin-bottom: 20px;">
                                We have successfully received your inquiry and appreciate your interest in Senior Floors.
                            </p>
                            
                            <p style="color: #333; font-size: 16px; line-height: 1.6; margin-bottom: 20px;">
                                Our team will review your information and contact you shortly to discuss your flooring needs.
                            </p>
                            
                            <!-- Summary Box -->
                            <div style="background-color: #f8f9fa; border-left: 4px solid #1a2036; padding: 20px; margin: 30px 0; border-radius: 4px;">
                                <h3 style="color: #1a2036; margin-top: 0; font-size: 18px;">Your Information:</h3>
                                <table cellpadding="5" cellspacing="0" style="width: 100%;">
                                    <tr>
                                        <td style="color: #666; font-size: 14px; padding: 5px 0;"><strong>Name:</strong></td>
                                        <td style="color: #333; font-size: 14px; padding: 5px 0;">{$name}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #666; font-size: 14px; padding: 5px 0;"><strong>Email:</strong></td>
                                        <td style="color: #333; font-size: 14px; padding: 5px 0;">{$email}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #666; font-size: 14px; padding: 5px 0;"><strong>Phone:</strong></td>
                                        <td style="color: #333; font-size: 14px; padding: 5px 0;">{$phone}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #666; font-size: 14px; padding: 5px 0;"><strong>Zip Code:</strong></td>
                                        <td style="color: #333; font-size: 14px; padding: 5px 0;">{$zipcode}</td>
                                    </tr>
HTML;
    
    if (!empty($message)) {
        $html .= <<<HTML
                                    <tr>
                                        <td style="color: #666; font-size: 14px; padding: 5px 0; vertical-align: top;"><strong>Message:</strong></td>
                                        <td style="color: #333; font-size: 14px; padding: 5px 0;">{$message}</td>
                                    </tr>
HTML;
    }
    
    $html .= <<<HTML
                                </table>
                            </div>
                            
                            <p style="color: #333; font-size: 16px; line-height: 1.6; margin-top: 30px;">
                                If you have any questions or need immediate assistance, please don't hesitate to contact us.
                            </p>
                            
                            <p style="color: #333; font-size: 16px; line-height: 1.6; margin-top: 20px;">
                                Best regards,<br>
                                <strong style="color: #1a2036;">The Senior Floors Team</strong>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 20px 30px; text-align: center; border-radius: 0 0 8px 8px; border-top: 1px solid #e0e0e0;">
                            <p style="color: #666; font-size: 12px; margin: 0;">
                                This is an automated confirmation email. Please do not reply to this message.
                            </p>
                            <p style="color: #666; font-size: 12px; margin: 10px 0 0 0;">
                                © " . date('Y') . " Senior Floors. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    
    return $html;
}

/**
 * Versão texto simples do email (para clientes que não suportam HTML)
 */
function getEmailConfirmationText($lead_name, $lead_email, $lead_phone, $lead_zipcode, $lead_message = '') {
    $text = "Thank You, {$lead_name}!\n\n";
    $text .= "We have successfully received your inquiry and appreciate your interest in Senior Floors.\n\n";
    $text .= "Our team will review your information and contact you shortly to discuss your flooring needs.\n\n";
    $text .= "Your Information:\n";
    $text .= "Name: {$lead_name}\n";
    $text .= "Email: {$lead_email}\n";
    $text .= "Phone: {$lead_phone}\n";
    $text .= "Zip Code: {$lead_zipcode}\n";
    
    if (!empty($lead_message)) {
        $text .= "Message: {$lead_message}\n";
    }
    
    $text .= "\n";
    $text .= "If you have any questions or need immediate assistance, please don't hesitate to contact us.\n\n";
    $text .= "Best regards,\n";
    $text .= "The Senior Floors Team\n\n";
    $text .= "---\n";
    $text .= "This is an automated confirmation email. Please do not reply to this message.\n";
    $text .= "© " . date('Y') . " Senior Floors. All rights reserved.\n";
    
    return $text;
}
