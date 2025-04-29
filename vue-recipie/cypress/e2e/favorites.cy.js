describe('Favorites Page', () => {
  beforeEach(() => {
    // Login first
    cy.visit('/login')
    cy.get('[data-test="email-input"]').type('test@example.com')
    cy.get('[data-test="password-input"]').type('password123')
    cy.get('[data-test="submit-login"]').click()
    
    // Navigate to favorites
    cy.visit('/favorites')
  })

  it('should display the favorites page', () => {
    cy.get('h1').should('contain', 'My Favorites')
  })

  it('should show favorite recipes', () => {
    cy.get('.grid').should('exist')
  })

  it('should allow removing a recipe from favorites', () => {
    cy.get('.grid').first().find('button').click()
    cy.get('.text-gray-400').should('exist')
  })

  it('should show empty state when no favorites', () => {
    // Remove all favorites
    cy.get('.grid').find('button').each(($btn) => {
      cy.wrap($btn).click()
    })
    
    cy.get('.empty-state').should('be.visible')
  })

  it('should navigate to recipe details when clicking a favorite', () => {
    cy.get('.grid').first().click()
    cy.get('.modal').should('be.visible')
  })
}) 