import { render, screen } from '@testing-library/react';
import App from './App';

test('renders main navigation', () => {
  render(<App />);
  expect(screen.getByText(/blogs/i)).toBeInTheDocument();
  expect(screen.getByText(/contact/i, { selector: 'nav a' })).toBeInTheDocument();
});
