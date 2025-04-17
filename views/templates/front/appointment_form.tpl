<!-- <link rel="stylesheet" href="{$urls.base_url}modules/appointmentmanager/views/css/appointment-form.css">
<script src="{$urls.base_url}modules/appointmentmanager/views/js/appointment-form.js"></script> -->

{extends file='page.tpl'}

{block name='page_content'}

{capture name=path}
    {l s='Make an Appointment' mod='appointmentmanager'}
{/capture}

{* Use standard page-content-container for spacing *}
<section id="main">
    <div class="page-content page-cms"> {* Reuse CMS page styling *}

        {* Display potential success message from URL parameter *}
        {if isset($appointment_success) && $appointment_success}
            <div class="alert alert-success" role="alert">
                <p>{l s='Your appointment request has been successfully registered.' mod='appointmentmanager'}</p>
                {* Optional: Add a link back to home or account *}
                <a href="{$urls.pages.index}" class="btn btn-secondary btn-sm mt-2">{l s='Back to home' mod='appointmentmanager'}</a>
            </div>
        {/if}

        {* Display potential validation/submission errors *}
        {if isset($appointment_errors) && !empty($appointment_errors)}
            <div class="alert alert-danger" role="alert">
                <p>{l s='Please correct the following errors:' mod='appointmentmanager'}</p>
                <ul>
                    {foreach from=$appointment_errors item=error}
                        <li>{$error|escape:'html':'UTF-8'}</li>
                    {/foreach}
                </ul>
            </div>
        {/if}

        {* Only show the form if success message isn't displayed *}
        {if !isset($appointment_success) || !$appointment_success}
            <section class="page-content-box" id="appointment-form-section"> {* Add a box around the form *}
                <h2 class="text-center h2">{l s='Make an Appointment' mod='appointmentmanager'}</h2>
                <hr>

                <form action="{$action_url|escape:'html':'UTF-8'}" method="post" id="appointment-form" class="needs-validation"> {* Add needs-validation for potential JS validation later *}

                    <div class="row"> {* Use rows and columns for layout *}
                        <div class="col-md-6">
                             <div class="form-group">
                                <label for="lastname">{l s='Last Name' mod='appointmentmanager'}</label>
                                <input type="text" id="lastname" name="lastname" class="form-control" value="{if isset($submitted_data.lastname)}{$submitted_data.lastname|escape:'html':'UTF-8'}{/if}" required>
                                {* Optional: Add Bootstrap validation feedback *}
                                <div class="invalid-feedback">{l s='Please enter your last name.' mod='appointmentmanager'}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="firstname">{l s='First Name' mod='appointmentmanager'}</label>
                                <input type="text" id="firstname" name="firstname" class="form-control" value="{if isset($submitted_data.firstname)}{$submitted_data.firstname|escape:'html':'UTF-8'}{/if}" required>
                                <div class="invalid-feedback">{l s='Please enter your first name.' mod='appointmentmanager'}</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">{l s='Address' mod='appointmentmanager'}</label>
                        <input type="text" id="address" name="address" class="form-control" value="{if isset($submitted_data.address)}{$submitted_data.address|escape:'html':'UTF-8'}{/if}" required>
                        <div class="invalid-feedback">{l s='Please enter your address.' mod='appointmentmanager'}</div>
                    </div>

                    <div class="row">
                         <div class="col-md-4">
                             <div class="form-group">
                                <label for="postal_code">{l s='Postal Code' mod='appointmentmanager'}</label>
                                <input type="text" id="postal_code" name="postal_code" class="form-control" value="{if isset($submitted_data.postal_code)}{$submitted_data.postal_code|escape:'html':'UTF-8'}{/if}" required>
                                <div class="invalid-feedback">{l s='Please enter your postal code.' mod='appointmentmanager'}</div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="city">{l s='City' mod='appointmentmanager'}</label>
                                <input type="text" id="city" name="city" class="form-control" value="{if isset($submitted_data.city)}{$submitted_data.city|escape:'html':'UTF-8'}{/if}" required>
                                <div class="invalid-feedback">{l s='Please enter your city.' mod='appointmentmanager'}</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone">{l s='Phone' mod='appointmentmanager'} <span class="text-muted">({l s='Optional' mod='appointmentmanager'})</span></label>
                                <input type="tel" id="phone" name="phone" class="form-control" value="{if isset($submitted_data.phone)}{$submitted_data.phone|escape:'html':'UTF-8'}{/if}" aria-describedby="phoneHelp">
                                <small id="phoneHelp" class="form-text text-muted">{l s='Used only to contact you about the appointment.' mod='appointmentmanager'}</small>
                            </div>
                        </div>
                         <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">{l s='Email' mod='appointmentmanager'} <span class="text-muted">({l s='Optional' mod='appointmentmanager'})</span></label>
                                <input type="email" id="email" name="email" class="form-control" value="{if isset($submitted_data.email)}{$submitted_data.email|escape:'html':'UTF-8'}{/if}" aria-describedby="emailHelp">
                                <small id="emailHelp" class="form-text text-muted">{l s='Used only to contact you about the appointment.' mod='appointmentmanager'}</small>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <p>{l s='Please select two preferred time slots for your appointment.' mod='appointmentmanager'}</p>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="rdv_option_1">{l s='Preferred time slot' mod='appointmentmanager'}</label>
                                <select id="rdv_option_1" name="rdv_option_1" class="form-control custom-select" required>
                                    <option value="">{l s='-- Choose an option --' mod='appointmentmanager'}</option>
                                    {foreach from=$available_dates key=value item=label}
                                        <option value="{$value|escape:'html':'UTF-8'}" {if isset($submitted_data.rdv_option_1) && $submitted_data.rdv_option_1 == $value}selected{/if}>{$label|escape:'html':'UTF-8'}</option>
                                    {/foreach}
                                </select>
                                <div class="invalid-feedback">{l s='Please select your preferred time slot.' mod='appointmentmanager'}</div>
                            </div>
                        </div>
                         <div class="col-md-6">
                             <div class="form-group">
                                <label for="rdv_option_2">{l s='Alternative time slot' mod='appointmentmanager'}</label>
                                <select id="rdv_option_2" name="rdv_option_2" class="form-control custom-select" required>
                                    <option value="">{l s='-- Choose an option --' mod='appointmentmanager'}</option>
                                    {foreach from=$available_dates key=value item=label}
                                        <option value="{$value|escape:'html':'UTF-8'}" {if isset($submitted_data.rdv_option_2) && $submitted_data.rdv_option_2 == $value}selected{/if}>{$label|escape:'html':'UTF-8'}</option>
                                    {/foreach}
                                </select>
                                <div class="invalid-feedback">{l s='Please select an alternative time slot.' mod='appointmentmanager'}</div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    {* GDPR Checkbox *}
                    <div class="form-group form-check">
                        {* The value="1" is important for Tools::isSubmit('GDPR') to work reliably *}
                        <input type="checkbox" id="GDPR" name="GDPR" class="form-check-input" value="1" {if isset($submitted_data) && Tools::getValue('GDPR') == 1}checked{/if} required>
                        <label class="form-check-label" for="GDPR">
                            {l s='I accept the' mod='appointmentmanager'} <a href="{$privacy_policy_url|escape:'html':'UTF-8'}" target="_blank" rel="nofollow noopener">{l s='privacy policy' mod='appointmentmanager'}</a> <span class="text-danger">*</span> {* Indicate required *}
                        </label>
                         <div class="invalid-feedback">{l s='You must accept the privacy policy.' mod='appointmentmanager'}</div>
                    </div>

                    <div class="form-footer text-center mt-4"> {* Added margin-top *}
                        <input type="hidden" name="submitAppointment" value="1"> {* Hidden input to check submission *}
                        <button type="submit" class="btn btn-primary btn-lg"> {* Made button larger *}
                            {l s='Submit Request' mod='appointmentmanager'}
                        </button>
                    </div>

                </form>
            </section>
        {/if} {* End conditional form display *}

    </div> {* /.page-content *}
</section> {* /#main *}
{/block}