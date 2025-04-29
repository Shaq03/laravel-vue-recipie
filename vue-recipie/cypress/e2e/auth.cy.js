describe('Authentication', () => {
  beforeEach(() => {
    cy.visit('/login')
  })

  it('should register a new user successfully', () => {
    // Generate unique email to avoid conflicts
    const uniqueEmail = `test${Date.now()}@example.com`
    
    // Click the register link
    cy.get('a').contains('Register').click()
    
    // Fill in registration form
    cy.get('input[placeholder="Username"]').type('Test User')
    cy.get('input[placeholder="Email address"]').type(uniqueEmail)
    cy.get('input[placeholder="Password"]').type('password123')
    cy.get('input[placeholder="Confirm Password"]').type('password123')
    cy.get('button').contains('Register').click()
    
    // Should redirect to home page after successful registration
    cy.url().should('include', '/')
  })

  it('should show error with mismatched passwords', () => {
    cy.get('a').contains('Register').click()
    
    // Fill in registration form with mismatched passwords
    cy.get('input[placeholder="Username"]').type('Test User')
    cy.get('input[placeholder="Email address"]').type('test@example.com')
    cy.get('input[placeholder="Password"]').type('password123')
    cy.get('input[placeholder="Confirm Password"]').type('differentpassword')
    cy.get('button').contains('Register').click()
    
    // Should show error message
    cy.get('.text-red-500').should('be.visible')
    cy.get('.text-red-500').should('contain', 'Passwords do not match')
  })

  it('should login successfully', () => {
    cy.get('input[placeholder="Email address"]').type('test@example.com')
    cy.get('input[placeholder="Password"]').type('password123')
    cy.get('button').contains('Sign in').click()
    cy.url().should('include', '/')
  })

  it('should show error with invalid credentials', () => {
    cy.get('input[placeholder="Email address"]').type('wrong@example.com')
    cy.get('input[placeholder="Password"]').type('wrongpass')
    cy.get('button').contains('Sign in').click()
    cy.get('.text-red-500').should('be.visible')
  })

  it('should navigate between login and register pages', () => {
    // Start at login
    cy.get('a').contains('Register').click()
    cy.url().should('include', '/register')
    
    // Go back to login
    cy.get('a').contains('Sign in').click()
    cy.url().should('include', '/login')
  })
}) 