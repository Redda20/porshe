/// <reference types="Cypress" />

import MemberFields from '../../elements/pages/members/MemberFields';
import ChannelFieldForm from '../../elements/pages/channel/ChannelFieldForm';
import MemberCreate from '../../elements/pages/members/MemberCreate';

const memberCreate = new MemberCreate

const form = new ChannelFieldForm;

const page = new MemberFields

context('Member Field List', () => {

  before(function(){
    cy.task('db:seed')
    cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
    cy.eeConfig({ item: 'require_captcha', value: 'n' })
    cy.eeConfig({ item: 'allow_member_registration', value: 'y' })
    cy.eeConfig({ item: 'req_mbr_activation', value: 'none' })

    //copy templates
    cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' }).then(() => {
        cy.authVisit('admin.php?/cp/design')
    })
  })

  beforeEach(function() {
    cy.auth();

    page.load()
    cy.hasNoErrors()
  })

  it('shows the Member Field List page', () => {
    page.get('member_actions').should('exist')
    page.get('member_fields_table').should('exist')
    page.get('member_fields_create').should('exist')
    cy.get('.title-bar__title').contains('Custom Member Fields')
  })

  it('create text field', () => {
    page.get('member_fields_create').click()
    form.createField({
        type: 'Text Input',
        label: 'Shipping Method'
    })
    page.hasAlert('success')
    cy.visit('admin.php?/cp/members/fields')
    page.get('member_fields_table').should('contain', 'Shipping Method')
  })

  it('can not create field with duplicate name', () => {
    cy.visit('admin.php?/cp/members/fields/create')
    form.createField({
        type: 'File',
        label: 'News Image',
        fields: { allowed_directories: 2 }
    })
    page.hasError(cy.get('input[name="m_field_name"]'), 'This field must be unique')
  })

  it('create file field', () => {
    cy.visit('admin.php?/cp/members/fields/create')
    form.createField({
        type: 'File',
        label: 'Member Image',
        fields: { allowed_directories: 2 }
    })
    page.hasAlert('success')
  })

  it('buttons field, visible on registration', () => {
    page.get('member_fields_create').click()
    cy.get('[data-toggle-for="m_field_reg"]').click()
    form.createField({
        type: 'Selectable Buttons',
        label: 'My Buttons',
        fields: {
          field_pre_populate: 'n',
          field_list_items: "one\ntwo\nthree"
        }
    })
    page.hasAlert('success')
  })

  it('URL field, visible on registration', () => {
    page.get('member_fields_create').click()
    cy.get('[data-toggle-for="m_field_reg"]').click()
    form.createField({
        type: 'URL',
        label: 'Member URL'
    })
    page.hasAlert('success')
  })

  it('register member in CP and set custom fields', () => {
    cy.visit('admin.php?/cp/members/create');
    memberCreate.get('username').clear().type('ee-test-member')
    memberCreate.get('email').clear().type('eetest@expressionengine.com')
    memberCreate.get('password').clear().type('1Password')
    memberCreate.get('confirm_password').clear().type('1Password')

    cy.get('.selectable_buttons').find('[value=two]').parent().click()
    cy.get('input[placeholder="http://"]').clear().type('https://expressionengine.com/')

    cy.get("body").then($body => {
        if ($body.find("#fieldset-verify_password > .field-control > input").length > 0) {   //evaluates as true if verify is needed
            cy.get("#fieldset-verify_password > .field-control > input").type('password');
        }
    });

    cy.get('button').contains('Save').first().click()
    cy.hasNoErrors()

    //check profile
    cy.get('a:contains("ee-test-member")').first().click()
    cy.get('label:contains("Member URL")').parents('fieldset').find('input[type=text]').should('have.value', 'https://expressionengine.com/')
    cy.get('label:contains("My Buttons")').parents('fieldset').find('.checkbox-label__text:contains("two")').parents('label').should('have.class', 'active')

    //check profile on front-end
    cy.visit('index.php/mbr/profile/ee-test-member');
    cy.get('.my_buttons span').invoke('text').should('eq', 'two')
    cy.get('.member_url span').invoke('text').should('eq', 'https://expressionengine.com/')
  })

  it('register member on front-end and set custom fields', () => {
    cy.clearCookies()
    cy.visit('index.php/mbr/register');
    cy.get('#username').clear().type('fe-member');
    cy.get('#email').clear().type('fe-member@expressionengine.com');
    cy.get('#password').clear().type('1Password');
    cy.get('#password_confirm').clear().type('1Password');
    cy.get('#accept_terms').check();

    cy.get('label:contains("My Buttons")').parents('fieldset').find('select').select('two')
    cy.get('label:contains("Member URL")').parents('fieldset').find('input[type=text]').clear().type('https://expressionengine.com/')

    cy.get('#submit').click();

    cy.get('h1').invoke('text').then((text) => {
        expect(text).equal('Member Registration Home')//redirected successfully
    })
    cy.clearCookies()
    cy.hasNoErrors()

    // the fields shown on frontend
    cy.visit('index.php/mbr/profile/fe-member');
    cy.get('.my_buttons span').invoke('text').should('eq', 'two')
    cy.get('.member_url span').invoke('text').should('eq', 'https://expressionengine.com/')
  })

  it('edit profile on front-end', () => {
    cy.clearCookies()
    cy.visit('index.php/members/login')
    cy.get('input[name=username]').clear().type('fe-member')
    cy.get('input[name=password]').clear().type('1Password')
    cy.get('input[name="submit"').click()
    cy.visit('index.php/mbr/profile-edit');
    //initially not available the fields are not visible
    cy.get('label:contains("My Buttons")').should('not.exist')
    cy.get('label:contains("Member URL")').should('not.exist')
    cy.clearCookies()

    //set fields as visible
    cy.authVisit('admin.php?/cp/members/fields')
    cy.get('a:contains("My Buttons")').first().click()
    cy.get('[data-toggle-for="m_field_public"]').click()
    cy.get('body').type('{ctrl}', {release: false}).type('s')

    cy.visit('admin.php?/cp/members/fields')
    cy.get('a:contains("Member URL")').first().click()
    cy.get('[data-toggle-for="m_field_public"]').click()
    cy.get('body').type('{ctrl}', {release: false}).type('s')

    // try again
    cy.clearCookies()
    cy.visit('index.php/members/login')
    cy.get('input[name=username]').clear().type('fe-member')
    cy.get('input[name=password]').clear().type('1Password')
    cy.get('input[name="submit"').click()
    cy.visit('index.php/mbr/profile-edit');
    cy.get('label:contains("My Buttons")').parents('fieldset').find('select').select('three')
    cy.get('label:contains("Member URL")').parents('fieldset').find('input[type=text]').clear().type('https://expressionengine.com/some-page')

    cy.get('#submit').click();

    cy.hasNoErrors()

    // the fields shown on frontend
    cy.visit('index.php/mbr/profile/fe-member');
    cy.hasNoErrors()
    cy.get('.my_buttons span').invoke('text').should('eq', 'three')
    cy.get('.member_url span').invoke('text').should('eq', 'https://expressionengine.com/some-page')

    cy.visit('index.php/mbr/profile-edit');
    cy.get('label:contains("My Buttons")').parents('fieldset').find('select option[value=three]').should('be.selected')
    cy.get('label:contains("Member URL")').parents('fieldset').find('input[type=text]').should('have.value', 'https://expressionengine.com/some-page')
  })
})
