import { ref } from 'vue'
import { __ } from '@wordpress/i18n'

export function useEmailTemplates() {
  const templates = ref([
    {
      id: 'simple',
      name: __('Simple', 'schedula-smart-appointment-booking'),
      html: `
          <div style="background-color: #f6f6f6; padding: 20px; font-family: Arial, sans-serif;">
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 600px; margin: auto; background-color: #ffffff; border: 1px solid #dddddd;">
              <tr>
                <td style="background-color: {header_bg_color}; padding: 20px;">
                  <h2 style="color: {header_text_color}; margin: 0;">{email_subject}</h2>
                </td>
              </tr>
              <tr>
                <td style="padding: 20px;">
                  <div id="email-body-content" style="line-height: 1.6; color: #333333;">{email_body}</div>
                </td>
              </tr>
              <tr>
                <td style="border-top: 1px solid #dddddd; padding: 20px; font-size: 0.9em; color: #555555; text-align: center;">
                </td>
              </tr>
            </table>
          </div>
        `
    },
    {
      id: 'modern',
      name: __('Modern', 'schedula-smart-appointment-booking'),
      html: `
          <div style="margin: 0; padding: 0; width: 100%; background-color: #f2f4f6;">
            <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#f2f4f6">
              <tr>
                <td align="center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 600px; margin: 0 auto;">
                    <tr>
                      <td style="padding: 32px;">
                        <div style="background-color: #ffffff; border: 1px solid #e8e5ef; border-radius: 3px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                          <div style="background-color: {header_bg_color}; color: {header_text_color}; padding: 24px; border-bottom: 1px solid #e8e5ef; text-align: center; border-top-left-radius: 3px; border-top-right-radius: 3px;">
                            <h1 style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'; font-size: 24px; font-weight: bold; margin: 0;">{email_subject}</h1>
                          </div>
                          <div style="padding: 24px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'; font-size: 16px; line-height: 1.5; color: #51545e;">
                            <div id="email-body-content">{email_body}</div>
                          </div>
                          <div style="border-top: 1px solid #e8e5ef; padding: 24px; text-align: center; color: #8a8a8a; font-size: 12px;">
                          </div>
                        </div>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
          </div>
        `
    },
    {
      id: 'elegant',
      name: __('Elegant', 'schedula-smart-appointment-booking'),
      html: `
          <div style="background-color: #fbfaf8; padding: 10px; font-family: Georgia, 'Times New Roman', Times, serif;">
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 600px; margin: auto; background-color: #ffffff; border: 1px solid #e6e6e6;">
              <tr>
                <td align="center" style="padding: 20px; background-color: {header_bg_color};">
                  <h1 style="color: {header_text_color}; margin: 0; font-weight: normal;">{email_subject}</h1>
                </td>
              </tr>
              <tr>
                <td style="padding: 30px 20px;">
                  <div id="email-body-content" style="line-height: 1.7; color: #5c5c5c; font-size: 16px;">{email_body}</div>
                </td>
              </tr>
              <tr>
                <td style="border-top: 1px solid #e6e6e6; padding: 20px; text-align: center; font-size: 0.8em; color: #888888;">
                </td>
              </tr>
            </table>
          </div>
        `
    }
  ])

  return {
    templates
  }
}
