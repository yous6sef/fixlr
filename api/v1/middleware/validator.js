/**
 * FLIX Validation Utilities
 * Comprehensive input validation and sanitization
 */

class Validator {
  /**
   * Validate email format
   */
  static isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email) && email.length <= 254;
  }

  /**
   * Validate phone number (international format)
   */
  static isValidPhone(phone) {
    const phoneRegex = /^\+?[1-9]\d{1,14}$/;
    return phoneRegex.test(phone.replace(/\s/g, ''));
  }

  /**
   * Validate password strength
   * Min 8 chars, 1 uppercase, 1 lowercase, 1 number
   */
  static isValidPassword(password) {
    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/;
    return passwordRegex.test(password);
  }

  /**
   * Validate positive numbers
   */
  static isValidPrice(price) {
    const num = parseFloat(price);
    return !isNaN(num) && num > 0 && num <= 1000000;
  }

  /**
   * Validate latitude/longitude coordinates
   */
  static isValidCoordinates(lat, lng) {
    const latitude = parseFloat(lat);
    const longitude = parseFloat(lng);
    return (
      !isNaN(latitude) &&
      !isNaN(longitude) &&
      latitude >= -90 &&
      latitude <= 90 &&
      longitude >= -180 &&
      longitude <= 180
    );
  }

  /**
   * Validate UUID format
   */
  static isValidUUID(uuid) {
    const uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
    return uuidRegex.test(uuid);
  }

  /**
   * Validate enum value
   */
  static isValidEnum(value, allowedValues) {
    return allowedValues.includes(value);
  }

  /**
   * Validate date format (YYYY-MM-DD)
   */
  static isValidDate(dateString) {
    const date = new Date(dateString);
    return date instanceof Date && !isNaN(date);
  }

  /**
   * Validate rating (1-5)
   */
  static isValidRating(rating) {
    const num = parseFloat(rating);
    return !isNaN(num) && num >= 1 && num <= 5;
  }

  /**
   * Sanitize string input
   */
  static sanitizeString(input) {
    if (typeof input !== 'string') return '';
    return input
      .trim()
      .replace(/[<>]/g, '') // Remove angle brackets
      .substring(0, 500); // Max 500 chars
  }

  /**
   * Sanitize HTML (simple XSS prevention)
   */
  static sanitizeHTML(input) {
    if (typeof input !== 'string') return '';
    return input
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#x27;')
      .substring(0, 1000);
  }

  /**
   * Validate status value
   */
  static isValidOrderStatus(status) {
    const validStatuses = ['pending', 'accepted', 'in-progress', 'completed', 'cancelled'];
    return validStatuses.includes(status);
  }

  /**
   * Validate specialization
   */
  static isValidSpecialization(spec) {
    const validSpecs = [
      'plumbing',
      'electrical',
      'cleaning',
      'carpentry',
      'painting',
      'moving',
      'hvac',
      'landscaping',
      'appliance-repair',
      'general-maintenance',
    ];
    return validSpecs.includes(spec);
  }

  /**
   * Batch validate object properties
   */
  static validateObject(obj, schema) {
    const errors = {};

    for (const [field, rules] of Object.entries(schema)) {
      const value = obj[field];

      if (rules.required && !value) {
        errors[field] = `${field} is required`;
        continue;
      }

      if (value) {
        if (rules.type === 'email' && !this.isValidEmail(value)) {
          errors[field] = `${field} must be a valid email`;
        }

        if (rules.type === 'phone' && !this.isValidPhone(value)) {
          errors[field] = `${field} must be a valid phone number`;
        }

        if (rules.type === 'price' && !this.isValidPrice(value)) {
          errors[field] = `${field} must be a valid price (0-1000000)`;
        }

        if (rules.minLength && value.length < rules.minLength) {
          errors[field] = `${field} must be at least ${rules.minLength} characters`;
        }

        if (rules.maxLength && value.length > rules.maxLength) {
          errors[field] = `${field} must not exceed ${rules.maxLength} characters`;
        }

        if (rules.enum && !rules.enum.includes(value)) {
          errors[field] = `${field} must be one of: ${rules.enum.join(', ')}`;
        }

        if (rules.pattern && !rules.pattern.test(value)) {
          errors[field] = `${field} format is invalid`;
        }

        if (rules.custom && !rules.custom(value)) {
          errors[field] = rules.customMessage || `${field} is invalid`;
        }
      }
    }

    return {
      isValid: Object.keys(errors).length === 0,
      errors,
    };
  }
}

/**
 * Request validation middleware
 */
function validateRequest(schema) {
  return (req, res, next) => {
    const validation = Validator.validateObject(
      { ...req.body, ...req.query, ...req.params },
      schema
    );

    if (!validation.isValid) {
      return res.status(400).json({
        success: false,
        error: {
          code: 'VALIDATION_ERROR',
          message: 'Request validation failed',
          details: validation.errors,
          statusCode: 400,
        },
      });
    }

    next();
  };
}

module.exports = {
  Validator,
  validateRequest,
};
