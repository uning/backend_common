/*
 * Copyright (C) 2010 Softao.Org
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
package org.softao.jassandra;

import java.util.List;

/**
 * The Interface IColumnFamily.
 */
public interface IColumnFamily {

	/**
	 * Gets the key space.
	 * 
	 * @return the key space
	 */
	IKeySpace getKeySpace();

	/**
	 * Gets the name.
	 * 
	 * @return name of this column family
	 */
	String getName();

	/**
	 * Checks if is super.
	 * 
	 * @return if this column family is super.
	 */
	boolean isSuper();

	/**
	 * Creates the criteria.
	 * 
	 * @return the criteria to be executed
	 * @throws JassandraException
	 *             the jassandra exception
	 */
	ICriteria createCriteria() throws JassandraException;

	/**
	 * Deletes the column with specified <code>key</code> and
	 * <code>column</code>.
	 * 
	 * @param key
	 * @param column
	 * @throws JassandraException
	 */
	void delete(String key, IColumn column) throws JassandraException;

	/**
	 * Insert columns with specified <code>key</code>.
	 * 
	 * @param key
	 *            the key
	 * @param columns
	 *            the columns to be inserted
	 * @throws JassandraException
	 *             the jassandra exception
	 */
	void insert(String key, IColumn... columns) throws JassandraException;

	/**
	 * Insert columns with specified <code>key</code>.
	 * 
	 * @param key
	 *            the key
	 * @param columns
	 *            the columns
	 * @throws JassandraException
	 *             the jassandra exception
	 */
	void insert(String key, List<IColumn> columns) throws JassandraException;
}
