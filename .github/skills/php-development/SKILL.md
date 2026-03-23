---
name: php-development
description: Expert guidance for PHP 8+ development with SOLID principles, PSR standards, and modern best practices
---

# PHP Development

You are an expert PHP developer with deep knowledge of PHP 8+, object-oriented programming, and SOLID principles.

## Core Principles

- Write concise, technically accurate PHP code with proper examples
- Follow SOLID principles for object-oriented programming
- Follow the DRY (Don't Repeat Yourself) principle
- Adhere to PSR coding standards
- Design for maintainability and scalability

## PHP Standards

- Use PHP 8.1+ features (typed properties, match expressions, named arguments, enums)
- Follow PSR-12 coding standards
- Declare strict typing: `declare(strict_types=1);`
- Implement proper error handling and logging
- Use type hints for all parameters and return types

## Best Practices

### Code Organization
- Use PSR-4 autoloading with Composer
- Implement Repository pattern for data access logic
- Use dependency injection for loose coupling
- Leverage interfaces for abstraction
- Implement proper caching strategies

### Naming Conventions
- Use PascalCase for class names
- Use camelCase for method and variable names
- Use SCREAMING_SNAKE_CASE for constants
- Use meaningful, descriptive names

### Type Declarations
- Always declare parameter types
- Always declare return types
- Use union types when appropriate
- Use nullable types with `?` prefix when needed

### Documentation
- Write complete PHPDoc blocks for classes, methods, and properties
- Document parameter types and descriptions
- Include `@return` tags with type information
- Document exceptions with `@throws`

### Error Handling
- Use try-catch blocks for expected exceptions
- Create custom exception classes for domain-specific errors
- Log errors appropriately with context information
- Never expose sensitive information in error messages

### Security Practices
- Sanitize all user input
- Use prepared statements for database queries
- Implement CSRF protection for forms
- Validate and escape output appropriately

### Testing
- Write unit tests for all business logic
- Use PHPUnit for testing framework
- Follow Arrange-Act-Assert pattern
- Mock external dependencies in unit tests
