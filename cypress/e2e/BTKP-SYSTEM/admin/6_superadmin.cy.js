describe('Testing Sistem Super Admin dan Proteksi Hak Akses', () => {

  beforeEach(() => {
    cy.clearCookies();
    cy.clearLocalStorage();
    cy.visit('/index.php');
  });

  it('TC-01: Super Admin berhasil login dengan kredensial bootstrap (admin_btkp)', () => {
    cy.get('input[name="username"]').type('admin_btkp');
    cy.get('input[name="password"]').type('abc_123');
    cy.get('button[type="submit"]').click();

    cy.url().should('include', '/admin_magang/dashboard.php');
    cy.contains('Super Admin').should('be.visible');
  });

  it('TC-02: Memvalidasi label Menu Sidebar telah diperbarui menjadi Kuota Magang', () => {
    cy.get('input[name="username"]').type('admin');
    cy.get('input[name="password"]').type('admin123');
    cy.get('button[type="submit"]').click();

    cy.get('.sidebar').contains('Kuota Magang').should('be.visible').click();
    cy.url().should('include', 'kuota.php');
    cy.contains('Pengaturan Kuota Magang').should('be.visible');
  });

  it('TC-03: Proteksi Akun Super Admin di halaman Manajemen Admin', () => {
    cy.get('input[name="username"]').type('admin');
    cy.get('input[name="password"]').type('admin123');
    cy.get('button[type="submit"]').click();

    cy.visit('/admin_magang/admin.php');

    // Memastikan akun Super Admin memiliki badge perlindungan dan tidak memiliki tombol hapus
    cy.contains('tr', 'admin_btkp').within(() => {
      cy.contains('Super Admin').should('be.visible');
      cy.contains('Terlindungi').should('be.visible');
      cy.get('button[title="Hapus Akun"]').should('not.exist');
    });
  });

});
