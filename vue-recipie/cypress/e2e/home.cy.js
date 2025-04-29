describe('Home Page', () => {
  beforeEach(() => {
    cy.visit('/')
  })

  it('should load the home page', () => {
    cy.get('h1').should('contain', 'Discover & Share Amazing Recipes')
  })

  it('should display features section', () => {
    cy.get('h2').should('contain', 'Everything You Need')
    cy.get('p').should('contain', 'Cook with Confidence')
    cy.get('.grid').should('exist')
  })

  it('should redirect to login when clicking Browse Recipes without authentication', () => {
    cy.get('a').contains('Browse Recipes').click()
    cy.url().should('include', '/login')
  })

  it('should have a call to action section', () => {
    cy.get('h2').should('contain', 'Ready to start cooking?')
    cy.get('a').contains('Get Started').should('exist')
  })
}) 